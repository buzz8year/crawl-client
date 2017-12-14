<?php

namespace backend\models\parser\source;

use backend\models\parser\ParserSourceInterface;
use backend\models\parser\ParserProvisioner;
use backend\models\parser\Parser;
use backend\models\CategorySource;
use backend\models\Region;
use backend\models\Source;


class Book24Parser extends Parser implements ParserSourceInterface
{
    const ACTION_SEARCH  = 'search/?';
    const QUERY_CATEGORY = 'section_id=';
    const QUERY_KEYWORD  = 'q=';

    const XPATH_WARNING = ''; // At Catalog/Search Page
    const XPATH_CATALOG = '//div[@class=\'book-list js-book-list\']//div[@class=\'book-list__item js-book-list-item js-catalog-item-element\']'; // At Catalog/Search Page

    const XPATH_SUPER       = ''; // At Product Page. JS Script with JSON Whole Data Object
    const XPATH_ATTRIBUTE   = '//div[@class=\'rowCover\']'; // At Product Page
    const XPATH_DESCRIPTION = '//div[@id=\'bookDescription\']//div[@class=\'text\']'; // At Product Page
    const XPATH_IMAGE       = '//img[@class=\'magnifyImage\']'; // At Product Page. Full size.

    const CATEGORY_NODE  = '//div[@class=\'leftTypeMenu\']/div[@class=\'itemCover\']'; // At HomePage navmenu
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

        if ($response = $this->sessionClient(self::$model->domain)) {
            if (($nodes = $this->getNodes($response, self::CATEGORY_NODE)) && $nodes->length) {
                foreach ($nodes as $key => $node) {
                    foreach ($node->getElementsByTagName('a') as $link) {
                        if ($link->getAttribute('href')) {
                            $exp = explode('-', $link->getAttribute('href'));
                            if ($link->parentNode === $node) {
                                $data[$key] = [
                                    'csid'       => trim(end($exp), '/'),
                                    'dump'       => '',
                                    'alias'      => '',
                                    'href'       => self::$model->domain . $link->getAttribute('href'),
                                    'title'      => trim($link->textContent),
                                    'nest_level' => 0,
                                ];
                            } else {
                                $data[$key]['children'][] = [
                                    'csid'       => trim(end($exp), '/'),
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
     * Extracting data from the product item's element of a category/search page
     * @return array
     */
    // public function getProducts(\DOMNodeList $nodes)
    public function getProducts($nodes)
    {
        $data = [];
        foreach ($nodes as $node) {
            foreach ($node->getElementsByTagName('*') as $child) {
                if (strpos($child->getAttribute('class'), 'publishingBookCard__link') !== false) {
                    $href = $child->getAttribute('href');
                }
                if (strpos($child->getAttribute('class'), 'publishingBookCard__title') !== false) {
                    $title = $child->textContent;
                }
                if (strpos($child->getAttribute('class'), 'publishingBookCard__author') !== false) {
                    $author = $child->textContent;
                }
                if (strpos($child->getAttribute('class'), 'publishingBookCard__price-value') !== false) {
                    $price = preg_replace('/[^0-9]/', '', $child->textContent);
                }
            }
            $data[] = [
                'price' => $price ?? null,
                'name'  => trim($title) . '. ' . trim($author),
                'href'  => self::$model->domain . $href,
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
            foreach ($node->getElementsByTagName('div') as $div) {
                if ($div->getAttribute('class') == 'leftTD') {
                    $data[$key]['title'] = trim($div->textContent);
                }
                if ($div->getAttribute('class') == 'rightTD') {
                    $data[$key]['value'] = trim($div->textContent);
                }
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
                'fullsize' => $node->getAttribute('src'),
                'thumb'  => '',
            ];
        }
        return $data;
    }

    public function pageQuery(int $page, string $url)
    {
        $page++;
        $pageQuery = '';

        if (strpos($url, '?') !== false) {
            $pageQuery = '&page=page-';
        } else {
            $pageQuery = '?page=page-';
        }

        return $page > 1 ? $pageQuery . $page : '';
    }

    public function urlBuild(string $regionSourceId = '', string $categorySourceId = '', string $keyword = '')
    {
        $domain   = self::$model->domain;
        $category = CategorySource::findOne($categorySourceId);

        $url = '';

        if ($categorySourceId && !$keyword) {
            $url = $this->processUrl($category->source_url);
        }

        if ($categorySourceId && $keyword) {
            $url = $domain . '/' . self::ACTION_SEARCH . self::QUERY_CATEGORY . $category->source_category_id . '&' . self::QUERY_KEYWORD . $keyword;
        }

        if (!$categorySourceId && $keyword) {
            $url = $domain . '/' . self::ACTION_SEARCH . '&' . self::QUERY_KEYWORD . $keyword;
        }

        return $url;
    }

}
