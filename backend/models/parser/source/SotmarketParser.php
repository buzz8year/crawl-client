<?php

namespace backend\models\parser\source;

use backend\models\parser\ParserSourceInterface;
use backend\models\parser\ParserProvisioner;
use backend\models\parser\Parser;
use backend\models\CategorySource;
use backend\models\Region;
use backend\models\Source;


class SotmarketParser extends Parser implements ParserSourceInterface
{
    const ACTION_SEARCH  = 'search/?';
    const QUERY_CATEGORY = '';
    const QUERY_KEYWORD  = 'q=';

    const XPATH_WARNING = ''; // At Catalog/Search Page
    const XPATH_CATALOG = '//div[@data-conf-identifier=\'products-tile\']//div[@itemprop=\'itemListElement\']'; // At Catalog/Search Page

    const XPATH_SUPER       = ''; // At Product Page. JS Script with JSON Whole Data Object
    const XPATH_ATTRIBUTE   = ''; // At Product Page
    const XPATH_DESCRIPTION = ''; // At Product Page
    const XPATH_IMAGE       = ''; // At Product Page. Full size.

    const CATEGORY_NODE  = '//a[contains(@class, \'b-header-nav-item\')]'; // At HomePage navmenu
    // const CATEGORY_WRAP_NODE  = '//*[contains(@class, \'sub-wrap\')]'; // At HomePage navmenu
    // const CATEGORY_WRAP_CLASS = 'catalog-subcatalog'; // At Level One Category Page leftmenu

    const CURL_FOLLOW = 1; // CURLOPT_FOLLOWLOCATION

    const MAX_QUANTITY = '';

    const DEFINE_CLIENT = 'curl'; // CURLOPT_FOLLOWLOCATION

    const PAGER_SPLIT_URL = true;

    static $region;

    /**
     * @return array
     */
    public function parseCategories()
    {
        $data = [];

        if ($response = $this->sessionClient(self::$model->domain)) {
            if (($nodes = $this->getNodes($response, self::CATEGORY_NODE)) && $nodes->length) {
                foreach ($nodes as $node) {
                    $index = json_decode($node->getAttribute('data-index'));
                    if (count($index) == 1) {
                        $data[$index[0]] = [
                            'csid'       => '',
                            'dump'       => '',
                            'alias'      => '',
                            'href'       => self::$model->domain . $node->getAttribute('href'),
                            'title'      => trim($node->textContent),
                            'nest_level' => 0,
                        ];
                    }
                    if (count($index) == 2) {
                        $data[$index[0]]['children'][$index[1]] = [
                            'csid'       => '',
                            'dump'       => '',
                            'alias'      => '',
                            'href'       => self::$model->domain . $node->getAttribute('href'),
                            'title'      => trim($node->textContent),
                            'nest_level' => 1,
                        ];
                    }
                    if (count($index) == 3) {
                        $data[$index[0]]['children'][$index[1]]['children'][$index[2]] = [
                            'csid'       => '',
                            'dump'       => '',
                            'alias'      => '',
                            'href'       => self::$model->domain . $node->getAttribute('href'),
                            'title'      => trim($node->textContent),
                            'nest_level' => 2,
                        ];
                    }
                }
            }
        }

        return $data;
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
        foreach ($nodes as $node) {
            $object = json_decode($node->getAttribute('data-conf-render-data'));
            $data[] = [
                'price' => $object->price,
                'name'  => $object->name,
                'href'  => $object->url,
            ];
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

    public function pageQuery(int $page, string $url, bool $explode = true)
    {
        $page++;
        $pageQuery = '';

        $pageQuery = '';

        if (strpos($url, '?') !== false) {
            $explode = explode('?', $url);
            $returnPage = $explode[0] . 'pagenum-' . $page . '.html?' . $explode[1];
        } else {
            $explode = explode('.html', $url);
            $returnPage = $explode[0] . '/pagenum-' . $page . '.html';
        }

        return $page > 1 ? $returnPage : $url;
    }

    public function urlBuild(string $regionSourceId = '', string $categorySourceId = '', string $keyword = '')
    {
        $domain   = self::$model->domain;
        $category = CategorySource::findOne($categorySourceId);

        $keyword = urlencode(iconv('utf-8', 'windows-1251//IGNORE', str_replace(' ', '+', $keyword)));

        $url = '';

        if ($categorySourceId && !$keyword) {
            $url = $category->source_url;
        }

        // if ($categorySourceId && $keyword) {
        //     $url = $domain . '/' . self::ACTION_SEARCH . $category->source_category_alias . '/?' . self::QUERY_KEYWORD . $keyword;
        // }

        if (!$categorySourceId && $keyword) {
            $url = $domain . '/' . self::ACTION_SEARCH . self::QUERY_KEYWORD . $keyword;
        }

        return $url;
    }

}
