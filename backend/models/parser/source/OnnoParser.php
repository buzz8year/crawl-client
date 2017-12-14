<?php

namespace backend\models\parser\source;

use backend\models\parser\ParserSourceInterface;
use backend\models\parser\ParserProvisioner;
use backend\models\parser\Parser;
use backend\models\CategorySource;
use backend\models\Region;
use backend\models\Source;


class OnnoParser extends Parser implements ParserSourceInterface
{
    const ACTION_SEARCH  = 'search/?';
    const QUERY_CATEGORY = '';
    const QUERY_KEYWORD  = 'value=';

    const XPATH_WARNING = ''; // At Catalog/Search Page
    const XPATH_CATALOG = '//div[contains(@class, \'content\')]//div[@class=\'catalog\']//div[@class=\'catalogItemContent\']'; // At Catalog/Search Page

    const XPATH_SUPER       = ''; // At Product Page. JS Script with JSON Whole Data Object
    const XPATH_ATTRIBUTE   = '//*[@class=\'cardInfoRow\']'; // At Product Page
    const XPATH_DESCRIPTION = '//*[@id=\'cardAbout\']'; // At Product Page
    const XPATH_IMAGE       = '//div[@class=\'cardImg\']'; // At Product Page. Full size.

    const CATEGORY_NODE  = '//div[@class=\'sideNavItemMain\']'; // At HomePage navmenu
    // const CATEGORY_WRAP_NODE  = '//div[@class=\'sideNavList\')]'; // At HomePage navmenu
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
                                    'href'       => self::$model->domain . $link->getAttribute('href'),
                                    'title'      => trim($link->textContent),
                                    'nest_level' => 0,
                                ];
                            }
                            if (trim($link->getAttribute('class')) == 'sideNavSubCategory'
                                || trim($link->getAttribute('class')) == 'sideNavSubItem') {
                                    $data[$key]['children'][] = [
                                        'csid'       => '',
                                        'dump'       => '',
                                        'alias'      => '',
                                        'href'       => self::$model->domain . $link->getAttribute('href'),
                                        'title'      => trim($link->textContent),
                                        'nest_level' => 1,
                                    ];
                            }
                        }
                    }
                }
            }
        }

        usort($data, function($a, $b) {
            return (count($b['children']) - count($a['children']));
        });

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
        $extend = ' and (contains(translate(string(), \'SALE\', \'sale\'), \'sale\') or .//div[contains(@class, \'catalogItemPriceOld\')])';
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
            $title = $href = $price = '';
            foreach ($node->getElementsByTagName('*') as $child) {
                if (strpos($child->getAttribute('class'), 'catalogItemName') !== false) {
                    $title = $child->textContent;
                    $href = $child->getAttribute('href');
                }
                if (strpos($child->getAttribute('class'), 'catalogItemPrice colorGreen') !== false) {
                    $price = preg_replace('/[^0-9]/', '', $child->textContent);
                }
            }
            if ($href && $price) {
                $data[] = [
                    'price' => $price,
                    'name'  => $title,
                    'href'  => self::$model->domain . $href,
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
            $data[] = [
                'title' => '',
                'text' => trim($node->nodeValue),
            ];
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
        foreach ($object as $node) {
            $data[] = [
                'title' => trim($node->getElementsByTagName('*')[0]->textContent),
                'value' => trim($node->getElementsByTagName('*')[1]->textContent),
            ];
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
        if ($object[0]) {
            if ($object[0]->getElementsByTagName('a')->length) {
                foreach ($object[0]->getElementsByTagName('a') as $node) {
                    $data[] = [
                        'fullsize' => $node->getAttribute('href'),
                        'thumb'    => $node->getElementsByTagName('img')[0]->getAttribute('src'),
                    ];
                }
            } else {
                $data[] = [
                    'fullsize' => $object[0]->getElementsByTagName('img')[0]->getAttribute('src'),
                    'thumb'    => '',
                ];
            }
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

        $returnPage = $page > 1 ? $pageQuery . $page : '';

        return $returnPage;
    }

    public function urlBuild(string $regionSourceId = '', string $categorySourceId = '', string $keyword = '')
    {
        $domain   = self::$model->domain;
        $category = CategorySource::findOne($categorySourceId);

        if ($category) {
            $categoryUrl = $category->source_url;
            if (strpos($category->source_url, $domain) !== false) {
                $categoryUrl = str_replace($domain, '', $category->source_url);
            }
        }

        $keyword = str_replace(' ', '+', $keyword);

        $url = '';

        if ($categorySourceId && !$keyword) {
            $url = $domain . $categoryUrl;
        }

        // if ($categorySourceId && $keyword) {
        //     $url = $domain . $categoryUrl . '?' . self::QUERY_KEYWORD . $keyword;
        // }

        if (!$categorySourceId && $keyword) {
            $url = $domain . '/' . self::ACTION_SEARCH . self::QUERY_KEYWORD . $keyword;
        }

        return $url;
    }

}
