<?php

namespace crawler\parser;

use crawler\parser\interface\ParserProvisionerInterface;
use crawler\models\category\CategorySource;
use crawler\models\category\Category;
use crawler\models\keyword\Keyword;
use crawler\models\source\Source;
use crawler\models\region\Region;

/**
 * ParserProvisioner prepares and provides data to the ParserController.
 */
class ParserProvisioner implements ParserProvisionerInterface
{
    /**
     * @inheritdoc
     */
    public static function listSources()
    {
        $sources = [];
        foreach (Source::find()->all() as $source) 
        {
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
    public static function listActiveSources()
    {
        $sources = [];
        foreach (Source::findAllActive() as $source) 
        {
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
    public static function listSourceRegions(int $sourceId)
    {
        $regions = Region::findAllAsArrayByRegionSourceId($sourceId);

        $sourceRegions = [];
        foreach ($regions as $region)
            $sourceRegions[$region['id']] = $region['alias'];

        return $sourceRegions;
    }

    /**
     * @inheritdoc
     */
    public static function listActiveCategories(int $sourceId)
    {
        $maxNest = CategorySource::getMaxNestLevelBySourceId($sourceId);
        $categories = Category::findAllAsArrayBySourceAndMaxNestLevel($sourceId, $maxNest);

        $sourceCategories = [];
        foreach ($categories as $category) 
        {
            if ($category['source_url']) 
            {
                $sourceCategories[] = [
                    'id' => $category['id'],
                    'csid' => $category['csid'],
                    'title' => $category['title'],
                ];
            } 
            else {
                $children = Category::findChildrenAsArrayByCategorySourceId($category['csid']);
                foreach ($children as $child) 
                {
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
    public static function listSourceCategories(int $sourceId)
    {
        $categories = Category::findAllAsArrayBySourceId($sourceId);

        $sourceCategories = [];
        foreach ($categories as $category)
            $sourceCategories[$category['id']] = $category['title'];

        return $sourceCategories;
    }

    /**
     * @inheritdoc
     */
    public static function listSourceKeywords(int $sourceId)
    {
        $keywords = Keyword::findAllAsArrayBySourceId($sourceId);

        $sourceKeywords = [];
        foreach ($keywords as $word)
            $sourceKeywords[$word['id']] = $word['word'];

        return $sourceKeywords;
    }

    /**
     * @inheritdoc
     */
    public static function buildTree(array $categories, int $parentId = null)
    {
        $tree = [];
        foreach ($categories as $category) 
        {
            if (is_object($category))
                $category = (array)$category;

            if ($category['self_parent_id'] == $parentId) 
            {
                $tree[$category['id']] = Category::findAsArrayByCategorySourceId($category['id']);
                $tree[$category['id']]['children'] = self::buildTree($categories, $category['id']);
            }
            unset($category);
        }
        return $tree;
    }

}
