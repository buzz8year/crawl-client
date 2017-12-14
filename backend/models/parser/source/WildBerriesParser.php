<?php

namespace backend\models\parser\source;

use backend\models\CategorySource;
use backend\models\parser\Parser;
use backend\models\parser\ParserSourceInterface;
use backend\models\Source;

class WildBerriesParser extends Parser implements ParserSourceInterface
{
    const ACTION_SEARCH  = 'catalog/0/search.aspx?';
    const QUERY_CATEGORY = 'subject=';
    const QUERY_KEYWORD  = 'search=';

    const XPATH_WARNING = ''; // At Catalog/Search Page
    const XPATH_CATALOG = '//a[@class=\'ref_goods_n_p\']'; // At Catalog/Search Page

    const XPATH_SUPER       = ''; // At Product Page. JS Script with JSON Whole Data Object
    const XPATH_ATTRIBUTE   = '//p[@class=\'pp color\' or @class=\'pp composition\' or @class=\'pp\']'; // At Product Page
    const XPATH_DESCRIPTION = '//div[@id=\'description\']'; // At Product Page
    const XPATH_IMAGE       = '//div[@id=\'scrollImage\']//a[contains(@class, \'image\')]'; // At Product Page. Full size.

    const CATEGORY_NODE      = ''; // At HomePage navmenu
    const CATEGORY_TREE_NODE = '//div[@id=\'sitemap\']/ul'; // At HomePage navmenu
    // const CATEGORY_WRAP_CLASS = 'catalog-subcatalog'; // At Level One Category Page leftmenu

    const CURL_FOLLOW = 0; // CURLOPT_FOLLOWLOCATION

    const MAX_QUANTITY = '';

    // const DEFINE_CLIENT = 'phantom'; // CURLOPT_FOLLOWLOCATION

    static $region;

    /**
     * @return array
     */
    public function parseCategories()
    {
        $data = [];

        if ($response = $this->curlSession(self::$model->domain . '/services/karta-sayta')) {
            if (($nodes = $this->getNodes($response, self::CATEGORY_TREE_NODE)) && $nodes->length) {
                foreach ($nodes as $key => $node) {
                    if ($tree = $this->nestCategories($node)) {
                        $data[] = $tree;
                    }

                    if ($key == 2) {
                        break;
                    }
                }
            }
        }

        return $data;
    }

    /**
     * @return array
     */
    public function nestCategories($node, $nestLevel = -1)
    {   
        $tree = [];
        $nestLevel++;
        foreach ($node->childNodes[0]->childNodes as $child) {
            if ($child->nodeName == 'a') {
                $exp = explode('/', $child->getAttribute('href'));
                if ($exp[0] != 'https:') {
                    $tree = [
                        'csid'       => '',
                        'dump'       => '',
                        'alias'      => '',
                        'nest_level' => $nestLevel,
                        'title'      => trim($child->textContent),
                        'href'       => self::$model->domain . $child->getAttribute('href'),
                    ];
                }
            }
            if ($child->nodeName == 'ul') {
                $tree['children'][] = $this->nestCategories($child, $nestLevel);
            }
        }
        return $tree;
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
        $extend = ' and .//span[@class=\'price\']/del';
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
        // print_r($nodes);
        foreach ($nodes as $node) {
            foreach ($node->getElementsByTagName('*') as $child) {
                if (strpos($child->getAttribute('class'), 'brand-name') !== false) {
                    $brand = $child->textContent;
                }
                if (strpos($child->getAttribute('class'), 'goods-name') !== false) {
                    $title = $child->textContent;
                }
                if (strpos($child->getAttribute('class'), 'price') !== false) {
                    if ($child->getElementsByTagName('del')->length) {
                        $price = preg_replace('/[^0-9]/', '', $child->getElementsByTagName('ins')[0]->textContent);
                    } else {
                        $price = preg_replace('/[^0-9]/', '', $child->textContent);
                    }
                }
            }
            $data[] = [
                'price' => $price,
                'name'  => $title . ' ' . $brand,
                'href'  => $node->getAttribute('href'),
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
        $data = [];
        foreach ($object as $node) {
            $data[] = [
                'title' => '',
                'text'  => $node->textContent,
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
        foreach ($object as $key => $node) {
            $exp = explode(':', $node->textContent);
            if (count($exp) == 2) {
                $data[$key]['title'] = trim($exp[0]);
                $data[$key]['value'] = trim($exp[1]);
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
                'fullsize' => $node->getAttribute('href'),
                'thumb'    => $node->getElementsByTagName('img')[0]->getAttribute('src'),
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

        return $pageQuery . $page;
    }

    public function urlBuild(string $regionSourceId = '', string $categorySourceId = '', string $keyword = '')
    {
        $domain   = self::$model->domain;
        $category = CategorySource::findOne($categorySourceId);

        $keyword = str_replace(' ', '+', $keyword);

        $url = '';

        if ($categorySourceId && !$keyword) {
            $url = $this->processUrl($category->source_url);
        }

        // if ($categorySourceId && $keyword) {
        //     $url = $domain . $category->source_url . self::ACTION_SEARCH . self::QUERY_KEYWORD . $keyword;
        // }

        if (!$categorySourceId && $keyword) {
            $url = $domain . '/' . self::ACTION_SEARCH . self::QUERY_KEYWORD . urlencode($keyword);
        }

        return $url;
    }

}
