<?php

namespace backend\models\parser\source;

use backend\models\parser\ParserSourceInterface;
use backend\models\parser\ParserProvisioner;
use backend\models\parser\Parser;
use backend\models\CategorySource;
use backend\models\Region;
use backend\models\Source;


class PleerParser extends Parser implements ParserSourceInterface
{
    const ACTION_SEARCH  = 'search_';

    const QUERY_CATEGORY = '';
    const QUERY_KEYWORD  = 'value=';

    const XPATH_WARNING = ''; // At Catalog/Search Page
    const XPATH_CATALOG = '//div[@class=\'text5 section_item content_main\']'; // At Catalog/Search Page

    const XPATH_SUPER       = ''; // At Product Page. JS Script with JSON Whole Data Object
    const XPATH_ATTRIBUTE   = ''; // At Product Page
    const XPATH_DESCRIPTION = '//div[@itemprop=\'description\']'; // At Product Page
    const XPATH_IMAGE       = '//td[@class=\'photo_self_section\']/a'; // At Product Page. Full size.

    const CATEGORY_NODE  = '//div[@class=\'top-menu-categories\']/ul/li'; // At HomePage navmenu
    // const CATEGORY_WRAP_NODE  = '//*[contains(@class, \'sub-wrap\')]'; // At HomePage navmenu
    // const CATEGORY_WRAP_CLASS = 'catalog-subcatalog'; // At Level One Category Page leftmenu

    const CURL_FOLLOW = 1; // CURLOPT_FOLLOWLOCATION

    const MAX_QUANTITY = 1000;

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
                foreach ($nodes as $key => $node) {
                    $data[$key] = [
                        'csid'       => '',
                        'dump'       => '',
                        'alias'      => '',
                        'href'       => '',
                        'title'      => ucfirst(trim($node->firstChild->textContent)),
                        'nest_level' => 0,
                    ];
                    foreach ($node->getElementsByTagName('a') as $link) {
                        if ($link->getAttribute('class') != 'top-menu-catalog-tree' 
                            && strpos($link->getAttribute('href'), 'javascript') === false) {
                                $data[$key]['children'][] = [
                                    'csid'       => '',
                                    'dump'       => '',
                                    'alias'      => '',
                                    'href'       => self::$model->domain . '/' . $link->getAttribute('href'),
                                    'title'      => ucfirst(trim($link->textContent)),
                                    'nest_level' => 1,
                                ];
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
        $extend = ' and (.//span[contains(@class, \'price_disk\')])';
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
            foreach ($node->getElementsByTagName('*') as $child) {
                if (strpos($child->getAttribute('class'), 'item_link h3') !== false) {
                    $title = $child->childNodes[0];
                }
                if ($child->getAttribute('itemprop') == 'price') {
                    $price = preg_replace('/[^0-9]/', '', $child->textContent);
                }
            }
            $data[] = [
                'price' => $price ?? null,
                'name'  => $title->textContent,
                'href'  => self::$model->domain . '/' . $title->getAttribute('href'),
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
                'thumb'  => $node->getElementsByTagName('img')[0]->getAttribute('src'),
            ];
        }
        return $data;
    }

    public function pageQuery(int $page, string $url)
    {
        $page++;

        $exp = explode('.html', $url)[0];

        $returnPage = $exp . '_page' . $page . '.html';

        return $returnPage;
    }

    public function urlBuild(string $regionSourceId = '', string $categorySourceId = '', string $keyword = '')
    {
        $category = CategorySource::findOne($categorySourceId);

        $keyword = str_replace(' ', '+', $keyword);

        $url = '';

        if ($categorySourceId && !$keyword) {
            $url = $this->processUrl($category->source_url);
        }

        // if ($categorySourceId && $keyword) {
        //     $url = $domain . $category->source_url . '?' . self::QUERY_KEYWORD . $keyword;
        // }

        if (!$categorySourceId && $keyword) {
            $url = self::$model->domain . '/' . self::ACTION_SEARCH . $keyword;
        }

        return $url;
    }

}
