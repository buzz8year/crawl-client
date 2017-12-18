<?php

namespace backend\models\parser\source;

use backend\models\parser\ParserSourceInterface;
use backend\models\parser\ParserProvisioner;
use backend\models\parser\Parser;
use backend\models\CategorySource;
use backend\models\Region;
use backend\models\Source;


class TechnopointParser extends Parser implements ParserSourceInterface
{   
    /**
     * curl / phantom
     */
    const DEFINE_CLIENT  = 'curl'; 

    const ACTION_SEARCH  = 'search/?';
    const QUERY_CATEGORY = '';
    const QUERY_KEYWORD  = 'q=';

    const XPATH_WARNING = ''; // At Catalog/Search Page
    // const XPATH_CATALOG = '//div[@data-id=\'product\']'; // At Product Page
    const XPATH_CATALOG = '//div[(@data-id=\'catalog-item\' or @data-id=\'product\')]'; // At Product Page

    const XPATH_SUPER       = ''; // At Product Page. JS Script with JSON Whole Data Object
    // const XPATH_ATTRIBUTE   = '//div[@id=\'characteristics\']//tr'; // At Product Page
    const XPATH_ATTRIBUTE   = '//div[@id=\'main-characteristics\']//tr'; // At Product Page
    const XPATH_DESCRIPTION = '//div[@itemprop=\'description\']/p'; // At Product Page
    const XPATH_IMAGE       = '//a[@data-fancybox-group=\'itemGroupThumbs\' and not(contains(@class, \'video\'))]'; // At Product Page. Full size.

    // const LEVEL_ONE_CATEGORY_NODE  = '//*[contains(@class, \'catalog-subcatalog\')]'; // At HomePage navmenu
    const CATEGORY_WRAP_NODE  = '//*[contains(@class, \'sub-wrap\')]'; // At HomePage navmenu
    const CATEGORY_WRAP_CLASS = 'catalog-subcatalog'; // At Level One Category Page leftmenu

    const CURL_FOLLOW = 1; // CURLOPT_FOLLOWLOCATION

    static $region;

    /**
     * @return array
     */
    public function parseCategories()
    {
        $data = [];

        if ($response = $this->phantomSession(self::$model->domain)) {
            // print_r($response);
            if (($wrappers = $this->getNodes($response, self::CATEGORY_WRAP_NODE)) && $wrappers->length) {
                foreach ($wrappers as $treeWrapper) {
                    if ($categoryZero = $treeWrapper->previousSibling) {

                        $data[] = [
                            'csid'       => '',
                            'dump'       => '',
                            'alias'      => '',
                            'href'       => $categoryZero->getAttribute('href'),
                            'title'      => trim($categoryZero->childNodes[1]->textContent),
                            'nest_level' => 0,
                            'children'   => $this->nestCategories($treeWrapper->childNodes[0]),
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
    public function nestCategories($treeWrapper, $nestLevel = 0)
    {
        $tree = [];
        if (strpos($treeWrapper->getAttribute('class'), self::CATEGORY_WRAP_CLASS) !== false && ($children = $treeWrapper->childNodes) && $children->length) {
            $nestLevel++;
            foreach ($children as $wrap) {
                $tree[] = [
                    'csid'       => '',
                    'dump'       => '',
                    'alias'      => '',
                    'nest_level' => $nestLevel,
                    'title'      => trim($wrap->childNodes[0]->childNodes[0]->textContent),
                    'href'       => $wrap->childNodes[0]->childNodes[0]->getAttribute('href'),
                    'children'   => $wrap->childNodes[1] ? $this->nestCategories($wrap->childNodes[1], $nestLevel) : [],
                ];
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
        $extend = ' and .//div[@class=\'previous-price\']';
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
            foreach ($node->getElementsByTagName('*') as $element) {
                if ($element->getAttribute('data-product-param') == 'name') {
                    $title = $element;
                }
                if ($element->getAttribute('data-product-param') == 'price') {
                    $price = $element;
                }
            }
            $data[] = [
                'name'  => $title->textContent,
                'href'  => self::$model->domain . $title->getAttribute('href'),
                'price' => $price->getAttribute('data-value'),
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
        foreach ($object as $key => $tr) {
            if ($tr->getElementsByTagName('td') && $tr->getElementsByTagName('td')->length == 2) {
                $data[$key]['title'] = $tr->getElementsByTagName('td')[0]->getElementsByTagName('div')[0]->textContent;
                $data[$key]['value'] = $tr->getElementsByTagName('td')[1]->textContent;
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
            $pageQuery = '&p=';
        } else {
            $pageQuery = '?p=';
        }

        return $page > 0 ? $pageQuery . $page : '';
    }

    public function urlBuild(string $regionSourceId = '', string $categorySourceId = '', string $keyword = '')
    {
        $category = CategorySource::findOne($categorySourceId);

        $keyword = str_replace(' ', '+', $keyword);

        $url = '';

        if ($categorySourceId && !$keyword) {
            $url = $this->processUrl($category->source_url);
        }

        if (!$categorySourceId && $keyword) {
            $url = self::$model->domain . '/' . self::ACTION_SEARCH . self::QUERY_KEYWORD . $keyword;
        }

        return $url;
    }

}
