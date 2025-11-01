<?php

namespace crawler\util;

use crawler\models\category\Category;
use yii\helpers\Html;

class CategoryNestedPrinter
{
    public static function printNest($category)
    {
        if (is_object($category))
            $category = (array)$category;

        $nest = $category['nest_level'] ?? 20;
        switch ($nest) 
        {
            case 0:
                $htmlDiv  = ['<div style="padding-left: 5px">', '</div>'];
                $htmlSpan = ['<span>', '</span>'];
                break;
            case 1: default:
                $htmlDiv  = ['<div style="padding-left: 20px">', '</div>'];
                $htmlSpan = ['<span>', '</span>'];
                break;
        }
        echo $htmlDiv[0];
        echo $htmlSpan[0];

        if ((isset($category['href']) && $category['href']) || (isset($category['source_url']) && $category['source_url']))
            echo Html::a($category['title'] ?? 'NONCAT', $category['href'] ?? $category['source_url']);
        
        else echo '<span class="text-muted">' . ($category['title'] ?? 'NONCAT') . '</span>';

        echo $htmlSpan[1];

        if (isset($category['children'])) 
        {
            foreach ($category['children'] as $child)
                self::printNest($child);
            echo '<br/>';
        }
        echo $htmlDiv[1];
    }

    public static function printNestedSelect(array $categories, int $sourceId, string $regionId = '', int $currentCategoryId = 0)
    {
        foreach ($categories as $categorySource) 
        {
            if (is_object($categorySource))
                $categorySource = (array)$categorySource;

            $title    = $categorySource['source_url'] ? $categorySource['source_url'] : 'Expand to select dub-category';
            $selected = $currentCategoryId == $categorySource['id'] ? 'selected' : '';
            $padding  = $categorySource['nest_level'] * 30 + 40;

            $onclick  = '';
            if ($categorySource['source_url'])
                $onclick = 'categoryOnSelect(' . $sourceId . ', ' . ($regionId ? $regionId : '\'\'') . ', ' . $categorySource['id'] . ');';

            $expand = '';
            if (isset($categorySource['children']) && count($categorySource['children']))
                $expand = '<span class="tree-expand ' . ($selected ? 'selected' : '') . '"></span>';

            $htmlWrap = $categorySource['nest_level'] == 0 
                ? ['<div class="col-xs-12"><div class="row expanded">', '</div></div>'] 
                : ['', ''];

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

            if (isset($categorySource['children']) && count($categorySource['children']))
                self::printNestedSelect($categorySource['children'], $sourceId, $regionId, $currentCategoryId);

            echo $htmlDiv[1];
            echo $htmlWrap[1];
        }
    }
}