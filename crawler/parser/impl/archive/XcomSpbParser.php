<?php

namespace crawler\parser\impl;

use crawler\parser\ParserSourceInterface;
use crawler\parser\ParserProvisioner;
use crawler\parser\Parser;
use crawler\models\CategorySource;
use crawler\models\Region;
use crawler\models\source\Source;


class XcomSpbParser extends Parser implements ParserSourceInterface
{
    const ACTION_SITEMAP = '/sitemap';
    const ACTION_SEARCH  = 'search/?';
    const QUERY_CATEGORY = '';
    const QUERY_KEYWORD  = 's=';

    const XPATH_WARNING = ''; // At Catalog/Search Page
    const XPATH_CATALOG = '//*[@class=\'item\']'; // At Catalog/Search Page

    const XPATH_SUPER       = ''; // At Product Page. JS Script with JSON Whole Data Object
    const XPATH_ATTRIBUTE   = '//tr[contains(@class, \'short_desc\')]'; // At Product Page
    const XPATH_DESCRIPTION = '//tr[contains(@class, \'wide_desc\')]'; // At Product Page
    const XPATH_IMAGE       = '//div[@id=\'card\']/table//td[@id=\'left_column\']//img'; // At Product Page. Full size.

    const IMAGE_SIZE = 500;
    const THUMB_SIZE = 60;

    const CATEGORY_NODE  = '//*[contains(@id, \'sitemap\')]/*[contains(@class, \'level\')]'; // At HomePage navmenu
    // const CATEGORY_WRAP_NODE  = '//*[contains(@class, \'sub-wrap\')]'; // At HomePage navmenu
    // const CATEGORY_WRAP_CLASS = 'catalog-subcatalog'; // At Level One Category Page leftmenu

    const CURL_FOLLOW = 1; // CURLOPT_FOLLOWLOCATION

    static $region;

    /**
     * @return array
     */
    public function parseCategories()
    {
        $data = [];

        if ($response = $this->curlSession('http://www.xcomspb.ru/sitemap')) {
            if (($categories = $this->getNodes($response, self::CATEGORY_NODE)) && $categories->length) {
                $data = $this->adoptCategories($categories);
            }
        }

        return $data;
    }

    /**
     * @return array
     */
    public function adoptCategories($categories, $parent = '')
    {
        $data = [];

        foreach ($categories as $key => $category) {
            $nestLevel = (int)explode('level', $category->getAttribute('class'))[1];
            $explode = explode('/', trim($category->childNodes[0]->getAttribute('href'), '/'));
            $alias = end($explode);
            $parent = $explode[count($explode) - 2];

            $data[$alias] = [
                'csid'       => '',
                'dump'       => '',
                'parent'     => $parent,
                'alias'      => $alias,
                'href'       => $category->childNodes[0]->getAttribute('href'),
                'title'      => trim($category->textContent),
                'nest_level' => $nestLevel,
            ];

            if ($key == 400) {
                break;
            }
        }

        $tree = $this->nestCategories($data);

        return $tree;
    }

    /**
     * @return array
     */
    public function nestCategories($categories, $parent = 'catalog')
    {
        $tree = [];
        foreach ($categories as $category) {
            if ($category['parent'] == $parent) {
                $tree[$category['alias']] = $category;
                $tree[$category['alias']]['children'] = $this->nestCategories($categories, $category['alias']);
            }
            unset($category);
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
     * Extracting data from the product item's element of a category/search page
     * @return array
     */
    // public function getProducts(\DOMNodeList $nodes)
    public function getProducts($nodes)
    {
        $data = [];

        foreach ($nodes as $node) {

            $price = '';
            foreach ($node->getElementsByTagName('*') as $element) {
                if (strpos($element->getAttribute('class'), 'cost-buy') !== false) {
                    $price = $element->getElementsByTagName('strong')[0]->textContent;
                }
            }
            $title = $node->getElementsByTagName('a')[1];
            $data[] = [
                'name'  => $title->textContent,
                'price' => preg_replace('/[^0-9]/', '', $price),
                'href'  => $this->handleUrl($title->getAttribute('href')),
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
            if ($node->getAttribute('src')) {
                $exp = explode('_', $node->getAttribute('src'));
                if (isset($exp[0])) {
                    $data[] = [
                        'fullsize' => $exp[0] . '_500.jpg',
                        'thumb'    => $exp[0] . '_60.jpg',
                    ];
                }
            }
        }
        return $data;
    }

    public function pageQuery(int $page, string $url)
    {
        $page++;
        $pageQuery = '';

        if (strpos($url, 'catalog') == false) {
            $pageQuery = '&search_page=';
        } else {
            $pageQuery = '?list_page=';
        }

        return $page > 0 ? $pageQuery . $page : '';
    }

    public function buildUrl(string $regionSourceId = '', string $categorySourceId = '', string $keyword = '')
    {
        $model    = self::$model;
        $domain   = self::$model->domain;
        $category = CategorySource::findOne($categorySourceId);

        $keyword = str_replace(' ', '+', $keyword);

        $url = '';

        if ($categorySourceId && !$keyword) {
            $url = $domain . $category->source_url;
        }

        if ($categorySourceId && $keyword) {
            $url = $domain . $category->source_url . self::ACTION_SEARCH . self::QUERY_KEYWORD . $keyword;
        }

        if (!$categorySourceId && $keyword) {
            $url = $domain . '/' . self::ACTION_SEARCH . self::QUERY_KEYWORD . $keyword;
        }

        return $url;
    }

}
