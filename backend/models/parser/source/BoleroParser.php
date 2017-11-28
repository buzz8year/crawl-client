<?php

namespace backend\models\parser\source;

use backend\models\parser\ParserSourceInterface;
use backend\models\parser\ParserProvisioner;
use backend\models\parser\Parser;
use backend\models\CategorySource;
use backend\models\Region;
use backend\models\Source;


class BoleroParser extends Parser implements ParserSourceInterface
{
    const ACTION_SEARCH  = '/search/';
    const QUERY_CATEGORY = '';
    const QUERY_KEYWORD  = '';

    const XPATH_WARNING = ''; // At Catalog/Search Page
    const XPATH_CATALOG = '//*[contains(@class, \'product_grid_item\')]'; // At Catalog/Search Page
    const XPATH_SEARCH  = '//div[@itemid=\'#product\']'; // At Catalog/Search Page

    const XPATH_SUPER       = ''; // At Product Page. JS Script with JSON Whole Data Object
    const XPATH_ATTRIBUTE   = ''; // At Product Page
    const XPATH_DESCRIPTION = ''; // At Product Page
    const XPATH_IMAGE       = ''; // At Product Page. Full size.

    const CATEGORY_NODE  = '//ul[@class=\'top-nav\']/li'; // At HomePage navmenu
    // const CATEGORY_WRAP_NODE  = '//*[contains(@class, \'sub-wrap\')]'; // At HomePage navmenu
    // const CATEGORY_WRAP_CLASS = 'catalog-subcatalog'; // At Level One Category Page leftmenu

    const CURL_FOLLOW = 1; // CURLOPT_FOLLOWLOCATION

    const MAX_QUANTITY = '';

    const DEFINE_CLIENT = 'curl'; // CURLOPT_FOLLOWLOCATION

    const PAGER_SPLIT_URL = false;

    static $region;
    static $template;

    /**
     * @return array
     */
    public function parseCategories()
    {
        $data = [];

        if ($response = $this->sessionClient(self::$model->domain)) {
            if (($nodes = $this->getNodes($response, self::CATEGORY_NODE)) && $nodes->length) {
                foreach ($nodes as $key => $node) {
                    foreach ($node->getElementsByTagName('a') as $link) {
                        if ($link->getAttribute('href')) {
                            if ($link->parentNode === $node) {
                                $data[$key] = [
                                    'csid'       => '',
                                    'dump'       => '',
                                    'alias'      => '',
                                    'href'       => $this->processUrl($link->getAttribute('href')),
                                    'title'      => trim($link->textContent),
                                    'nest_level' => 0,
                                ];
                            } else {
                                $data[$key]['children'][] = [
                                    'csid'       => '',
                                    'dump'       => '',
                                    'alias'      => '',
                                    'href'       => $this->processUrl($link->getAttribute('href')),
                                    'title'      => trim($link->textContent),
                                    'nest_level' => 1,
                                ];
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
     * Extracting data from the product item's element of a category/search page
     * @return array
     */
    public function getProducts(\DOMNodeList $nodes)
    {
        $data = [];
        foreach ($nodes as $node) {
            foreach ($node->getElementsByTagName('*') as $child) {
                if (self::$template == 'search') {
                    if (strpos($child->getAttribute('itemprop'), 'name') !== false) {
                        $title = $child;
                    }
                    if (strpos($child->getAttribute('itemprop'), 'price') !== false) {
                        $price = preg_replace('/[^0-9]/', '', $child->textContent);
                    }
                } else {
                    if (strpos($child->getAttribute('itemprop'), 'name') !== false) {
                        $title = $child;
                    }
                    if (strpos($child->getAttribute('class'), 'price') !== false) {
                        $price = preg_replace('/[^0-9]/', '', $child->textContent);
                    }
                }

            }
            $data[] = [
                'price' => $price ?? null,
                'name'  => trim($title->textContent),
                'href'  => $title->getAttribute('href'),
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

    public function pageQuery(int $page, string $url)
    {
        $page++;

        if (self::$template == 'search') {
            $pageQuery = '/p';
        } else {
            $pageQuery = '/';
        }
        
        return $page > 1 ? $pageQuery . $page : '';
    }

    public function urlBuild(string $regionSourceId = '', string $categorySourceId = '', string $keyword = '')
    {
        $category = CategorySource::findOne($categorySourceId);

        $url = '';

        if ($categorySourceId && !$keyword) {
            $url = $this->processUrl($category->source_url);
            self::$template = 'catalog';
        }

        // if ($categorySourceId && $keyword) {
        //     $url = $domain . '/' . self::ACTION_SEARCH . self::QUERY_CATEGORY . $category->source_category_id . '&' . self::QUERY_KEYWORD . $keyword;
        // }

        if (!$categorySourceId && $keyword) {
            $url = self::$model->domain . self::ACTION_SEARCH . $keyword;
            self::$template = 'search';
        }

        return $url;
    }

}
