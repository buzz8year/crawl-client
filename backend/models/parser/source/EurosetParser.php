<?php

namespace backend\models\parser\source;

use backend\models\parser\ParserSourceInterface;
use backend\models\parser\ParserProvisioner;
use backend\models\parser\Parser;
use backend\models\CategorySource;
use backend\models\Region;
use backend\models\Source;


class EurosetParser extends Parser implements ParserSourceInterface
{
    const ACTION_SEARCH  = 'search/?filter[]=products';
    const QUERY_CATEGORY = 'section_id=';
    const QUERY_KEYWORD  = 'q=';

    const XPATH_WARNING = ''; // At Catalog/Search Page
    const XPATH_CATALOG = '//a[@class=\'item-link link\']'; // At Catalog/Search Page

    const XPATH_SUPER       = ''; // At Product Page. JS Script with JSON Whole Data Object
    const XPATH_ATTRIBUTE   = ''; // At Product Page
    const XPATH_DESCRIPTION = ''; // At Product Page
    const XPATH_IMAGE       = ''; // At Product Page. Full size.

    const CATEGORY_NODE  = ''; // At HomePage navmenu
    // const CATEGORY_WRAP_NODE  = '//*[contains(@class, \'sub-wrap\')]'; // At HomePage navmenu
    // const CATEGORY_WRAP_CLASS = 'catalog-subcatalog'; // At Level One Category Page leftmenu

    const CURL_FOLLOW = 1; // CURLOPT_FOLLOWLOCATION

    const MAX_QUANTITY = '';

    const DEFINE_CLIENT = 'curl'; // CURLOPT_FOLLOWLOCATION

    static $region;

    /**
     * @return array
     */
    public function parseCategories()
    {
    }


    /**
     * @return array
     */
    public function getWarningData(\DOMNodeList $nodes)
    {
    }

    /**
     * Extracting data from the product item's element of a category/search page
     * @return array
     */
    public function getProducts(\DOMNodeList $nodes)
    {
        $data = [];
        print_r($nodes);
        foreach ($nodes as $node) {
            // foreach ($node->getElementsByTagName('*') as $child) {
            //     if (strpos($child->getAttribute('class'), 'catalogItemName') !== false) {
            //         $title = $child;
            //     }
            //     if (strpos($child->getAttribute('class'), 'catalogItemPrice colorGreen') !== false) {
            //         $price = preg_replace('/[^0-9]/', '', $child->textContent);
            //     }
            // }
            // $data[] = [
            //     'price' => $price ?? null,
            //     'name'  => $title->textContent,
            //     'href'  => $title->getAttribute('href'),
            // ];
        }

        return $data;
    }

    /**
     * Extracting an object (of all the data needed) from the <script/> element
     * @return object
     */
    public function getSuperData(\DOMNodeList $nodes)
    {
    }

    /**
     * Getting descriptions data from the object produced by getSuperData()
     * @return array
     */
    public function getDescriptionData($object)
    {
    }

    /**
     * Getting attributes data from the object produced by getSuperData()
     * @return array
     */
    public function getAttributeData($object)
    {
    }

    /**
     * Getting image data from the object produced by getSuperData()
     * @return array
     */
    public function getImageData($object)
    {
    }

    public function pageQuery(int $page, string $url)
    {
        $pageQuery = '';

        if (strpos($url, '?') !== false) {
            $pageQuery = '&page=';
        } elseif (strpos($url, '?') !== false) {
            $pageQuery = '&page=';
        } else {
            $pageQuery = '?page=';
        }

        return $page > 0 ? $pageQuery . $page : '';
    }

    public function urlBuild(string $regionSourceId = '', string $categorySourceId = '', string $keyword = '')
    {
        $domain   = self::$model->domain;
        $category = CategorySource::findOne($categorySourceId);

        $keyword = str_replace(' ', '+', $keyword);

        $url = '';

        if ($categorySourceId && !$keyword) {
            $url = $domain . $category->source_url;
        }

        // if ($categorySourceId && $keyword) {
        //     $url = $domain . $category->source_url . '?' . self::QUERY_KEYWORD . $keyword;
        // }

        if (!$categorySourceId && $keyword) {
            $url = $domain . '/' . self::ACTION_SEARCH . '&' . self::QUERY_KEYWORD . $keyword;
        }

        return $url;
    }

}
