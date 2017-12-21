<?php

namespace backend\models\parser;

use backend\models\CategorySource;
use backend\models\Category;
use backend\models\Keyword;
use backend\models\Region;
use backend\models\Source;
use yii\helpers\Html;

/**
 * ParserProvisioner prepares and provides data to the ParserController.
 */
class ParserProvisioner implements ParserProvisioningInterface
{
    /**
     * @inheritdoc
     */
    public static function listSources()
    {
        $sources = [];

        foreach (Source::find()->all() as $source) {
            $sources[$source->id] = [
                'domain' => $source->source_url,
                'status' => $source->status,
                'title'  => $source->title,
            ];
        }

        return $sources;
    }

    /**
     * @inheritdoc
     */
    public static function activeSources()
    {
        $sources = [];

        foreach (Source::find()->where(['status' => 1])->all() as $source) {
            $sources[$source->id] = [
                'domain' => $source->source_url,
                'status' => $source->status,
                'title'  => $source->title,
            ];
        }

        return $sources;
    }

    /**
     * @inheritdoc
     */
    public function listSourceRegions(int $sourceId)
    {
        $sourceRegions = [];
        $regions       = Region::find()
            // ->select(['category.id', 'category.title', 'cs.source_id as source'])
            ->join('join', 'region_source rs', 'rs.region_id = region.id')
            ->where('rs.source_id = ' . $sourceId)
            ->distinct(true)
            ->asArray()
            ->all();

        foreach ($regions as $region) {
            $sourceRegions[$region['id']] = $region['alias'];
        }

        return $sourceRegions;
    }

    /**
     * @inheritdoc
     */
    public static function activeCategories(int $sourceId)
    {
        $sourceCategories = [];

        $maxNest = CategorySource::find()
            ->where('source_id = ' . $sourceId)
            ->max('nest_level');

        $categories = Category::find()
            ->select(['category.id as id', 'cs.id as csid', 'category.title', 'cs.source_url'])
            ->join('join', 'category_source cs', 'cs.category_id = category.id')
            ->where('cs.nest_level = ' . $maxNest . ' AND cs.source_id = ' . $sourceId)
            // ->where('cs.nest_level = 0 AND cs.source_id = ' . $sourceId)
            // ->where('category.status = 1 AND cs.source_id = ' . $sourceId)
            ->distinct(true)
            ->asArray()
            ->all();

        foreach ($categories as $category) {
            if ($category['source_url']) {
                $sourceCategories[] = [
                    'id' => $category['id'],
                    'csid' => $category['csid'],
                    'title' => $category['title'],
                ];
            } else {
                $children = Category::find()
                    ->select(['category.id as id', 'cs.id as csid', 'category.title', 'cs.source_url'])
                    ->join('join', 'category_source cs', 'cs.category_id = category.id')
                    ->where('cs.self_parent_id = ' . $category['csid'])
                    ->distinct(true)
                    ->asArray()
                    ->all();

                foreach ($children as $child) {
                    $sourceCategories[] = [
                        'id' => $child['id'],
                        'csid' => $child['csid'],
                        'title' => $child['title'],
                    ];
                }
            }
        }

        return $sourceCategories;
    }

    /**
     * @inheritdoc
     */
    public function listSourceCategories(int $sourceId)
    {
        $sourceCategories = [];

        $categories = Category::find()
            ->select(['category.id', 'category.title', 'cs.source_id as source'])
            ->join('join', 'category_source cs', 'cs.category_id = category.id')
            ->where('cs.source_id = ' . $sourceId)
            ->distinct(true)
            ->asArray()
            ->all();

        foreach ($categories as $category) {
            $sourceCategories[$category['id']] = $category['title'];
        }

        return $sourceCategories;
    }

    /**
     * @inheritdoc
     */
    public function listSourceKeywords(int $sourceId)
    {
        $sourceKeywords = [];

        $keywords = Keyword::find()
            ->select(['keyword.id', 'keyword.word', 'ks.source_id as source'])
            ->join('join', 'keyword_source ks', 'ks.keyword_id = keyword.id')
            ->where('ks.source_id = ' . $sourceId)
            ->distinct(true)
            ->asArray()
            ->all();

        foreach ($keywords as $word) {
            $sourceKeywords[$word['id']] = $word['word'];
        }

        return $sourceKeywords;
    }

    /**
     * @inheritdoc
     */
    public static function buildTree(array $categories, int $parentId = null)
    {
        $tree = [];
        foreach ($categories as $category) {
            if (is_object($category)) {
                $category = (array)$category;
            }
            if ($category['self_parent_id'] == $parentId) {
                $tree[$category['id']] = Category::find()
                    ->select('*')
                    ->join('join', 'category_source cs', 'cs.category_id = category.id')
                    ->where(['cs.id' => $category['id']])
                    ->asArray()
                    ->one();

                $tree[$category['id']]['children'] = self::buildTree($categories, $category['id']);
            }
            unset($category);
        }

        return $tree;
    }

    /**
     * @inheritdoc
     */
    public static function displayNest($category)
    {
        if (is_object($category)) {
            $category = (array)$category;
        }

        $nest = isset($category['nest_level']) ? $category['nest_level'] : 20;

        switch ($nest) {
            case 0:
                $htmlDiv  = ['<div style="padding-left: 5px">', '</div>'];
                $htmlSpan = ['<span>', '</span>'];
                break;
            case 1:
                $htmlDiv  = ['<div style="padding-left: 20px">', '</div>'];
                $htmlSpan = ['<span>', '</span>'];
                break;
            default:
                $htmlDiv  = ['<div style="padding-left: 20px">', '</div>'];
                $htmlSpan = ['<span>', '</span>'];
                break;
        }

        echo $htmlDiv[0];
        echo $htmlSpan[0];

        if ((isset($category['href']) && $category['href']) || (isset($category['source_url']) && $category['source_url'])) {
            echo Html::a($category['title'] ?? 'NONCAT', $category['href'] ?? $category['source_url']);
        } else {
            echo '<span class="text-muted">' . ($category['title'] ?? 'NONCAT') . '</span>';
        }

        echo $htmlSpan[1];

        if (isset($category['children'])) {
            foreach ($category['children'] as $child) {
                self::displayNest($child);
            }
            echo '<br/>';
        }

        echo $htmlDiv[1];
    }

    /**
     * @inheritdoc
     */
    public static function displayNestedSelect(array $categories, int $sourceId, string $regionId = '', int $currentCategoryId = null)
    {
        // $html = '';

        foreach ($categories as $categorySource) {

            if (is_object($categorySource)) {
                $categorySource = (array)$categorySource;
            }

            $title    = $categorySource['source_url'] ? $categorySource['source_url'] : 'В рамках данного ресурса это раздел - не категория, - раскройте и выберите категорию имеющую адрес.';
            $selected = $currentCategoryId == $categorySource['id'] ? 'selected' : '';
            $padding  = $categorySource['nest_level'] * 30 + 40;

            $onclick  = '';
            if ($categorySource['source_url']) {
                $onclick = 'categoryOnSelect(' . $sourceId . ', ' . ($regionId ? $regionId : '\'\'') . ', ' . $categorySource['id'] . ');';
            }

            $expand = '';
            if (isset($categorySource['children']) && count($categorySource['children'])) {
                $expand = '<span class="tree-expand ' . ($selected ? 'selected' : '') . '"></span>';
            }

            $htmlWrap = $categorySource['nest_level'] == 0 ? [
                '<div class="col-xs-12"><div class="row expanded">',
                '</div></div>',
            ] : ['', ''];
            $htmlDiv = [
                $expand . '<div class="category-tree-row">',
                '</div>',
            ];

            $htmlSpan = [
                '<span class="category-tree-select ' . $selected . '" style="padding-left: ' . $padding . 'px" onclick="' . $onclick . '" title="' . $title . '">', 
                '</span>'
            ];

            echo $htmlWrap[0];
            echo $htmlDiv[0];
            echo $htmlSpan[0];
            echo $categorySource['title'] ? $categorySource['title'] : 'NONCAT';
            echo $htmlSpan[1];

            if (isset($categorySource['children']) && count($categorySource['children'])) {
                self::displayNestedSelect($categorySource['children'], $sourceId, $regionId, $currentCategoryId);
            }

            echo $htmlDiv[1];
            echo $htmlWrap[1];

        }

        // return $html;

    }

}
