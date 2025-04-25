<?php

namespace crawler\models\parser\source;

use crawler\models\parser\ParserSourceInterface;
use crawler\models\parser\ParserProvisioner;
use crawler\models\parser\Parser;
use crawler\models\CategorySource;
use crawler\models\Region;
use crawler\models\source\Source;


class ZeroThreeParser extends Parser implements ParserSourceInterface
{
    const ACTION_SEARCH  = 'search?';
    const QUERY_CATEGORY = 'partition_id=';
    const QUERY_KEYWORD  = 'q=';

    const XPATH_WARNING = ''; // At Catalog/Search Page
    const XPATH_CATALOG = '//div[@class=\'product_grid_item\']'; // At Catalog/Search Page

    const XPATH_SUPER       = ''; // At Product Page. JS Script with JSON Whole Data Object
    const XPATH_ATTRIBUTE   = '//div[@id=\'toupdate\']//tr'; // At Product Page
    const XPATH_DESCRIPTION = '//div[@id=\'toupdate\']//div[@class=\'border2\']/noindex[1]/div[2]'; // At Product Page
    const XPATH_IMAGE       = '//div[@class=\'small_images_wrapper\']//img'; // At Product Page. Full size.

    const CATEGORY_NODE  = '//ul[@class=\'menu_1\']/li'; // At HomePage navmenu
    // const CATEGORY_WRAP_NODE  = '//*[contains(@class, \'sub-wrap\')]'; // At HomePage navmenu
    // const CATEGORY_WRAP_CLASS = 'catalog-subcatalog'; // At Level One Category Page leftmenu

    const CURL_FOLLOW = 0; // CURLOPT_FOLLOWLOCATION

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
                            } else {
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
        $extend = ' and .//div[contains(@class, \'old_price\')]';
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
                if (strpos($child->getAttribute('itemprop'), 'name') !== false) {
                    $title = $child;
                }
                if (strpos($child->getAttribute('class'), 'price text-center') !== false) {
                    $price = preg_replace('/[^0-9]/', '', $child->childNodes[0]->textContent);
                }
            }
            $data[] = [
                'price' => $price ?? null,
                'name'  => $title->textContent,
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
                $data[$key]['title'] = $tr->getElementsByTagName('td')[0]->textContent;
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
            $exp = explode('/', $node->getAttribute('src'));
            unset($exp[count($exp) - 1]);
            $data[] = [
                'fullsize' => implode('/', $exp) . '/473x385.jpg',
                'thumb'    => $node->getAttribute('src'),
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
            $url = self::$model->domain . '/' . self::ACTION_SEARCH . self::QUERY_KEYWORD . $keyword;
        }

        return $url;
    }

}
