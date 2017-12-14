<?php

namespace backend\models\parser\source;

use backend\models\parser\ParserSourceInterface;
use backend\models\parser\ParserProvisioner;
use backend\models\parser\Parser;
use backend\models\CategorySource;
use backend\models\Region;
use backend\models\Source;


class LiteMobileParser extends Parser implements ParserSourceInterface
{
    const ACTION_SEARCH  = '/search/?';
    const QUERY_CATEGORY = 'category_id=';
    const QUERY_KEYWORD  = 'search=';

    const XPATH_WARNING = ''; // At Catalog/Search Page
    const XPATH_CATALOG = '//*[contains(@class, \'card-item__list\')]'; // At Catalog/Search Page
    // const XPATH_SEARCH  = '//div[@itemid=\'#product\']'; // At Catalog/Search Page

    const XPATH_SUPER       = ''; // At Product Page. JS Script with JSON Whole Data Object
    const XPATH_ATTRIBUTE   = '//div[@id=\'tabs-1\']//li'; // At Product Page
    const XPATH_DESCRIPTION = ''; // At Product Page
    const XPATH_IMAGE       = '//div[@class=\'view-goods__left-paginator-item\']/img'; // At Product Page. Full size.

    const CATEGORY_TREE_NODE  = '//nav//a[contains(text(), \'Каталог товаров\')]'; // At HomePage navmenu
    // const CATEGORY_NODE  = ''; // At HomePage navmenu
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

        if ($response = $this->curlSession(self::$model->domain)) {
            if (($nodes = $this->getNodes($response, self::CATEGORY_TREE_NODE)) && $nodes->length) {
                $lis = $nodes[0]->nextSibling->nextSibling->childNodes;
                foreach ($lis as $li) {
                    if ($li->nodeName == 'li') {
                        $data[] = $this->nestCategories($li);
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
        foreach ($node->childNodes as $child) {
            if ($child->nodeName == 'a') {
                $tree = [
                    'csid'       => '',
                    'dump'       => '',
                    'alias'      => '',
                    'nest_level' => $nestLevel,
                    'title'      => trim($child->textContent),
                    'href'       => $child->getAttribute('href'),
                ];
            }
            if ($child->nodeName == 'ul') {
                foreach ($child->childNodes as $child) {
                    if ($child->nodeName == 'li') {
                        $tree['children'][] = $this->nestCategories($child, $nestLevel);
                    }
                }
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
     * Extracting data from the product item's element of a category/search page
     * @return array
     */
    // public function getProducts(\DOMNodeList $nodes)
    public function getProducts($nodes)
    {
        $data = [];
        foreach ($nodes as $node) {
            foreach ($node->getElementsByTagName('*') as $child) {
                if (strpos($child->getAttribute('class'), 'card-desc') !== false) {
                    $title = $child->getElementsByTagName('*')[0];
                }
                if (strpos($child->getAttribute('class'), 'card-price') !== false) {
                    $price = preg_replace('/[^0-9]/', '', $child->textContent);
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
        $data = [];
        foreach ($object as $key => $node) {
            $exp = explode(':', $node->textContent);
            if (count($exp) > 1) {
                $data[$key]['title'] = trim($exp[0]);
                $data[$key]['value'] = ltrim($node->textContent, $exp[0]);
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
            $exp = explode('-', $node->getAttribute('src'));
            unset($exp[count($exp) - 1]);
            $data[] = [
                'fullsize' => implode('-', $exp) . '-500x638.jpg',
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

        return $page > 0 ? $pageQuery . $page : '';
    }

    public function urlBuild(string $regionSourceId = '', string $categorySourceId = '', string $keyword = '')
    {
        $category = CategorySource::findOne($categorySourceId);

        $url = '';

        if ($categorySourceId && !$keyword) {
            $url = $this->processUrl($category->source_url);
        }

        // if ($categorySourceId && $keyword) {
        //     $url = $domain . '/' . self::ACTION_SEARCH . self::QUERY_CATEGORY . $category->source_category_id . '&' . self::QUERY_KEYWORD . $keyword;
        // }

        if (!$categorySourceId && $keyword) {
            $url = self::$model->domain . self::ACTION_SEARCH . self::QUERY_KEYWORD . $keyword;
        }

        return $url;
    }

}
