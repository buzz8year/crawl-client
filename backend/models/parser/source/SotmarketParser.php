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

    const QUERY_AVAILABLE = 'attr[is_available][]=1';

    const XPATH_WARNING = ''; // At Catalog/Search Page
    const XPATH_CATALOG = '//div[@data-conf-identifier=\'products-tile\']//div[@itemprop=\'itemListElement\']'; // At Catalog/Search Page

    const XPATH_SUPER       = ''; // At Product Page. JS Script with JSON Whole Data Object
    const XPATH_ATTRIBUTE   = '//*[contains(@class, \'b-goods-specifications-row\')]'; // At Product Page
    const XPATH_DESCRIPTION = '//*[contains(@class, \'mod_product-desc\')]//h3'; // At Product Page
    const XPATH_IMAGE       = '//*[@class=\'b-gallery-preview-image\']/img'; // At Product Page. Full size.

    const CATEGORY_NODE  = '//a[contains(@class, \'b-header-nav-item\')]'; // At HomePage navmenu
    // const CATEGORY_WRAP_NODE  = '//*[contains(@class, \'sub-wrap\')]'; // At HomePage navmenu
    // const CATEGORY_WRAP_CLASS = 'catalog-subcatalog'; // At Level One Category Page leftmenu

    const CURL_FOLLOW = 0; // CURLOPT_FOLLOWLOCATION

    const MAX_QUANTITY = '';

    const DEFINE_CLIENT = 'curl'; // CURLOPT_FOLLOWLOCATION

    const PAGER_SPLIT_URL = true;

    const PAGER_EXCUSE = true;

    static $region;

    /**
     * @return array
     */
    public function parseCategories()
    {
        $data = [];

        if ($response = $this->sessionClient(self::$model->domain)) {
            if (($nodes = $this->getNodes($response, self::CATEGORY_NODE)) && $nodes->length) {
                foreach ($nodes as $key => $node) {
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

                        if ($rspns = $this->sessionClient(self::$model->domain . $node->getAttribute('href'))) {
                            if (($nds = $this->getNodes($rspns, '//a[@class=\'g-font color_black\']')) && $nds->length) {
                                foreach ($nds as $nd) {
                                    $data[$index[0]]['children'][$index[1]]['children'][$index[2]]['children'][] = [
                                        'csid'       => '',
                                        'dump'       => '',
                                        'alias'      => '',
                                        'href'       => self::$model->domain . $nd->getAttribute('href'),
                                        'title'      => trim($nd->textContent),
                                        'nest_level' => 3,
                                    ];
                                }
                            }
                        }
                    }

                    // if ($key == 125) {
                    //     break;
                    // }
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
        $extend = ' and .//span[contains(@class, \'scheme_oldprice\')]';
        $explode  = rtrim($xpath, ']');
        $xpath = $explode . $extend . ']';

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
            $object = json_decode($node->getAttribute('data-conf-render-data'));
            // if ($object->url && $object->price) {
                $data[] = [
                    'name'  => $object->name,
                    'price' => $object->price,
                    'href'  => $this->processUrl($object->url),
                ];
            // }
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
        foreach ($object as $key => $node) {
            if ($node->nextSibling && $node->nextSibling->nextSibling->nodeName == 'p') {
                $data[$key] = [
                    'title' => $node->textContent,
                    'text'  => $node->nextSibling->nextSibling->textContent,
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
        $data = [];
        foreach ($object as $key => $node) {
            if ($node->getElementsByTagName('div') && $node->getElementsByTagName('div')->length == 2) {
                $data[$key]['title'] = $node->getElementsByTagName('div')[0]->textContent;
                $data[$key]['value'] = $node->getElementsByTagName('div')[1]->textContent;
            }
        }
        return $data;
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
                'fullsize' => $this->processUrl($node->getAttribute('src')),
                'thumb'    => ''
            ];
        }
        return $data;
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
            $url = $category->source_url . '?' . self::QUERY_AVAILABLE;
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
