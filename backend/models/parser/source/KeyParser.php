<?php

namespace backend\models\parser\source;

use backend\models\parser\ParserSourceInterface;
use backend\models\parser\ParserProvisioner;
use backend\models\parser\Parser;
use backend\models\CategorySource;
use backend\models\Region;
use backend\models\Source;


class KeyParser extends Parser implements ParserSourceInterface
{
    const ACTION_SEARCH  = 'search/custom_search/?';
    const QUERY_CATEGORY = '';
    const QUERY_KEYWORD  = 'search_string=';

    const XPATH_WARNING = ''; // At Catalog/Search Page
    const XPATH_CATALOG = '//div[@class=\'catalog-goods__image-view_item-inner\']'; // At Catalog/Search Page

    const XPATH_SUPER       = ''; // At Product Page. JS Script with JSON Whole Data Object
    const XPATH_ATTRIBUTE   = '//div[@class=\'catalog_object_characteristics_item\']'; // At Product Page
    const XPATH_DESCRIPTION = '//div[@itemprop=\'description\']'; // At Product Page
    const XPATH_IMAGE       = '//div[@data-bigphoto]'; // At Product Page. Full size.

    const CATEGORY_NODE  = '//div[@class=\'catalog_pid_block_cont\']'; // At HomePage navmenu
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
                        if ($link->parentNode->getAttribute('class') == 'title_line') {
                            $data[$key] = [
                                'csid'       => '',
                                'dump'       => '',
                                'alias'      => '',
                                'href'       => self::$model->domain . $link->getAttribute('href'),
                                'title'      => trim($link->textContent),
                                'nest_level' => 0,
                            ];
                        }
                        if ($link->parentNode->parentNode->getAttribute('class') == 'menu_line') {
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
        $extend = ' and .//span[contains(@class, \'rouble-price strike\')]';
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
            foreach ($node->childNodes as $child) {
                if (strpos($child->getAttribute('class'), 'link') !== false) {
                    $title = $child->childNodes[0];
                }
            }
            foreach ($node->getElementsByTagName('*') as $child) {
                if (strpos($child->getAttribute('class'), 'rouble-price cl_pink') !== false) {
                    $price = preg_replace('/[^0-9]/', '', $child->textContent);
                }
            }
            $data[] = [
                'price' => $price ?? null,
                'name'  => $title->textContent,
                'href'  => $this->processUrl($title->getAttribute('href')),
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
                'fullsize' => $this->processUrl($node->getAttribute('data-bigphoto')),
                'thumb'    => $this->processUrl($node->getAttribute('data-preview'))//div[@id=\'description\'],
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

        // return $pageQuery . $page;
        return $page > 1 ? $pageQuery . $page : '';
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

        if ($categorySourceId && $keyword) {
            $url = $this->processUrl($category->source_url) . '?' . self::QUERY_KEYWORD . $keyword;
        }

        if (!$categorySourceId && $keyword) {
            $url = $domain . '/' . self::ACTION_SEARCH . self::QUERY_KEYWORD . $keyword;
        }

        return $url;
    }

}
