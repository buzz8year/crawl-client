<?php

namespace backend\models\parser\source;

use backend\models\parser\ParserSourceInterface;
use backend\models\parser\ParserProvisioner;
use backend\models\parser\Parser;
use backend\models\CategorySource;
use backend\models\Region;
use backend\models\Source;


class BooksParser extends Parser implements ParserSourceInterface
{
    const ACTION_SEARCH  = 'search.php?s[go]=1';
    const QUERY_CATEGORY = 's[subcategory]=';
    const QUERY_KEYWORD  = 's[query]=';

    const XPATH_WARNING = ''; // At Catalog/Search Page
    const XPATH_CATALOG = '//div[@id=\'wares_list_block\']//div[@class=\'catalog\']//tr'; // At Catalog/Search Page

    const XPATH_SUPER       = ''; // At Product Page. JS Script with JSON Whole Data Object
    const XPATH_ATTRIBUTE   = ''; // At Product Page
    const XPATH_DESCRIPTION = '//div[@itemprop=\'description\']'; // At Product Page
    const XPATH_IMAGE       = '//meta[@itemprop=\'image\']'; // At Product Page. Full size.

    const CATEGORY_NODE  = '//div[@class=\'submenu\']/ul/li'; // At HomePage navmenu
    // const CATEGORY_WRAP_NODE  = '//*[contains(@class, \'sub-wrap\')]'; // At HomePage navmenu
    // const CATEGORY_WRAP_CLASS = 'catalog-subcatalog'; // At Level One Category Page leftmenu

    const CURL_FOLLOW = 1; // CURLOPT_FOLLOWLOCATION

    const MAX_QUANTITY = '';

    const DEFINE_CLIENT = 'curl'; // CURLOPT_FOLLOWLOCATION

    const PAGER_SPLIT_URL = false;

    static $region;

    /**
     * @return array
     */
    public function parseCategories()
    {
        $data = [];
        $dataTwo = [];

        if ($response = $this->sessionClient(self::$model->domain)) {
            if (($nodes = $this->getNodes($response, self::CATEGORY_NODE)) && $nodes->length) {
                foreach ($nodes as $key => $node) {
                    if ($node->getAttribute('class') == 'has_child') {
                        $top = $node->getElementsByTagName('a')[0];
                        $data[$top->getAttribute('href')] = [
                            'csid'       => '',
                            'dump'       => '',
                            'alias'      => '',
                            'href'       => $top->getAttribute('href'),
                            'title'      => trim($top->textContent) ?? '--',
                            'nest_level' => 0,
                        ];
                        foreach ($node->getElementsByTagName('a') as $key => $link) {
                            if ($key > 0) {
                                $data[$top->getAttribute('href')]['children'][$link->getAttribute('href')] = [
                                    'csid'       => '',
                                    'dump'       => '',
                                    'alias'      => '',
                                    'href'       => $link->getAttribute('href'),
                                    'title'      => trim($link->textContent) ?? '--',
                                    'nest_level' => 1,
                                ];
                                if ($resp = $this->sessionClient(self::$model->domain . $link->getAttribute('href'))) {
                                    if (($childs = $this->getNodes($resp, '//div[@class=\'owner-menu subcategory\']/ul/li/a')) && $childs->length) {
                                        foreach ($childs as $child) {
                                            $data[$top->getAttribute('href')]['children'][$link->getAttribute('href')]['children'][] = [
                                                'csid'       => '',
                                                'dump'       => '',
                                                'alias'      => '',
                                                'href'       => $child->getAttribute('href'),
                                                'title'      => trim($child->textContent) ?? '--',
                                                'nest_level' => 2,
                                            ];
                                        }
                                    }
                                }
                            }
                        }
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
     * @return
     */
    public static function xpathSale(string $xpath)
    {
        // $extend = ' and .//span[@class=\'price promo\']';
        $extend = '[.//span[@class=\'price promo\']]';
        // $explode  = rtrim($xpath, ']');
        // $xpath = $explode . $extend . ']';
        $xpath = $xpath . $extend;

        return $xpath;
    }




    /**
     * Extracting data from the product item's element of a category/search page
     * @return array
     */
    // public function getProducts(\DOMNodeList $nodes)
    public function getProducts($nodes)
    {
        $data = [];
        foreach ($nodes as $node) {
            foreach ($node->getElementsByTagName('*') as $child) {
                if (strpos($child->getAttribute('class'), 'title') !== false) {
                    $title = $child->getElementsByTagName('*')[0];
                }
                if (strpos($child->getAttribute('class'), 'price') !== false) {
                    $price = preg_replace('/[^0-9]/', '', $child->textContent);
                }
            }
            if (isset($title) && isset($price)) {
                $data[] = [
                    'price' => $price ?? null,
                    'name'  => $title->textContent,
                    'href'  => self::$model->domain . $title->getAttribute('href'),
                ];
            }
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
        $data = [];
        foreach ($object as $node) {
            $found = false;
            foreach ($node->parentNode->getElementsByTagName('div') as $div) {
                if ($div->getAttribute('class') == 'all_note') {
                    $data[] = [
                        'title' => '',
                        'text' => $div->textContent,
                    ];
                    $found = true;
                }
            }
            if (!$found) {
                $data[] = [
                    'title' => '',
                    'text' => $node->textContent,
                ];
            }
        }
        return $data;
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
        $data = [];
        foreach ($object as $node) {
            $data[] = [
                'fullsize' => $node->getAttribute('content'),
                'thumb' => '',
            ];
        }
        return $data;
    }

    public function pageQuery(int $page, string $url)
    {
        $page++;
        $pageQuery = '';

        if (strpos($url, '?') !== false) {
            $pageQuery = '&page=';
        } else {
            $pageQuery = '?page=';
        }

        return $page > 1 ? $pageQuery . $page : '';
    }

    public function urlBuild(string $regionSourceId = '', string $categorySourceId = '', string $keyword = '')
    {
        $domain   = self::$model->domain;
        $category = CategorySource::findOne($categorySourceId);

        $url = '';

        if ($categorySourceId && !$keyword) {
            $url = $domain . $category->source_url;
        }

        if ($categorySourceId && $keyword) {
            $url = $domain . '/' . self::ACTION_SEARCH . '&' . self::QUERY_CATEGORY . $category->source_category_id . '&' . self::QUERY_KEYWORD . $keyword;
        }

        if (!$categorySourceId && $keyword) {
            $url = $domain . '/' . self::ACTION_SEARCH . '&' . self::QUERY_KEYWORD . $keyword;
        }

        return $url;
    }

}
