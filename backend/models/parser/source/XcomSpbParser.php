<?php

namespace backend\models\parser\source;

use backend\models\parser\ParserSourceInterface;
use backend\models\parser\ParserProvisioner;
use backend\models\parser\Parser;
use backend\models\CategorySource;
use backend\models\Region;
use backend\models\Source;


class XcomSpbParser extends Parser implements ParserSourceInterface
{
    const ACTION_SITEMAP = '/sitemap';
    const ACTION_SEARCH  = 'search/?';
    const QUERY_CATEGORY = '';
    const QUERY_KEYWORD  = 's=';

    const XPATH_WARNING = ''; // At Catalog/Search Page
    const XPATH_CATALOG = '//*[@class=\'item\']'; // At Catalog/Search Page

    const XPATH_SUPER       = ''; // At Product Page. JS Script with JSON Whole Data Object
    const XPATH_ATTRIBUTE   = ''; // At Product Page
    const XPATH_DESCRIPTION = '//*[contains(@class, \'item-description-text\')]'; // At Product Page
    const XPATH_IMAGE       = '//*[contains(@class, \'gallery-extended-img-frame\')]'; // At Product Page. Full size.

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

            // if ($key == 200) {
            //     break;
            // }
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
    public function getProducts(\DOMNodeList $nodes)
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
        if (isset($object->Description)) {
            foreach ($object->Description->Blocks as $descScope) {
                $data[] = [
                    'title' => $descScope->Title,
                    'text'  => $descScope->Text,
                ];
            }
        } else {
            foreach ($object as $node) {
                $data[] = [
                    'title' => '',
                    'text'  => $node->textContent,
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
    }

    /**
     * Getting image data from the object produced by getSuperData()
     * @return array
     */
    public function getImageData($object)
    {
        $data = [];
        if (isset($object->Gallery)) {
            foreach ($object->Gallery->Groups[0]->Elements as $imageScope) {
                $data[] = [
                    'fullsize' => $imageScope->Original,
                    'thumb'    => $imageScope->Preview,
                ];
            }
        } else {
            foreach ($object as $node) {
                $data[] = [
                    'fullsize' => $node->getAttribute('data-url'),
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

        if (strpos($url, 'catalog') == false) {
            $pageQuery = '&search_page=';
        } else {
            $pageQuery = '?list_page=';
        }

        return $page > 0 ? $pageQuery . $page : '';
    }

    public function urlBuild(string $regionSourceId = '', string $categorySourceId = '', string $keyword = '')
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
