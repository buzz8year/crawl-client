<?php

namespace backend\models\parser\source;

use backend\models\parser\ParserSourceInterface;
use backend\models\parser\ParserProvisioner;
use backend\models\parser\Parser;
use backend\models\CategorySource;
use backend\models\Region;
use backend\models\Source;


class VoltParser extends Parser implements ParserSourceInterface
{
    const ACTION_SEARCH  = 'search/';
    const QUERY_CATEGORY = '';
    const QUERY_KEYWORD  = 'q=';

    const XPATH_WARNING = ''; // At Catalog/Search Page
    const XPATH_CATALOG = '//*[@id=\'product-list\']//*[@class=\'box-inline v-top\']'; // At Catalog/Search Page

    const XPATH_SUPER       = ''; // At Product Page. JS Script with JSON Whole Data Object
    const XPATH_ATTRIBUTE   = '//table[contains(@class, \'table-property\')]//td[@class=\'phspace-5\']'; // At Product Page
    const XPATH_DESCRIPTION = '//div[@itemprop=\'description\']//div[contains(@id, \'description\')]'; // At Product Page
    const XPATH_IMAGE       = '//a[contains(@class, \'product-gallery-thumbnail\') or @itemprop=\'image\']'; // At Product Page. Full size.

    // const CATEGORY_NODE  = ''; // At HomePage navmenu
    const CATEGORY_WRAP_NODE  = '//ul[contains(@class, \'js-menu\')]/li'; // At HomePage navmenu
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
        $dataTwo = [];

        if ($response = $this->sessionClient(self::$model->domain)) {
            if (($nodes = $this->getNodes($response, self::CATEGORY_WRAP_NODE)) && $nodes->length) {
                foreach ($nodes as $key => $node) {
                    $zero = $node->getElementsByTagName('a')[0];
                    if (strpos($zero->getAttribute('href'), 'discount') === false) {
                        $data[$key] = [
                            'csid'       => '',
                            'dump'       => '',
                            'alias'      => '',
                            'href'       => $zero->getAttribute('href'),
                            'title'      => trim($zero->textContent),
                            'nest_level' => 0,
                        ];
                    }


                    if ($ul = $node->getElementsByTagName('ul')[0]) {
                        foreach ($ul->getElementsByTagName('li') as $child) {
                            if ($child->parentNode === $ul) {
                                foreach ($child->getElementsByTagName('a') as $i => $link) {
                                    if ($i > 0) {
                                        $exp = explode('catalog/', $link->getAttribute('href'));
                                        if (isset($exp[1])) {
                                            $alias = $exp[1];
                                            $data[$key]['children'][] = [
                                                'csid'       => '',
                                                'dump'       => '',
                                                'alias'      => preg_match('/[a-z]/i', $alias) ? trim($alias, '/') : '',
                                                'href'       => $link->getAttribute('href'),
                                                'title'      => trim($link->textContent),
                                                'nest_level' => 1,
                                            ];
                                        }
                                    }
                                }
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
        $extend = ' and .//div[contains(@class, \'new-item-list-discount\')]';
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
                if (strpos($child->getAttribute('class'), 'new-item-list-name') !== false) {
                    $title = $child->getElementsByTagName('*')[0];
                }
                if (strpos($child->getAttribute('class'), 'new-item-list-price-im') !== false) {
                    $price = preg_replace('/[^0-9]/', '', $child->textContent);
                }
            }
            $data[] = [
                'price' => $price ?? null,
                'name'  => $title->textContent,
                'href'  => self::$model->domain . $title->getAttribute('href'),
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
        foreach ($object as $node) {
            $data[] = [
                'title' => $node->getElementsByTagName('span')[0]->textContent,
                'value' => $node->getElementsByTagName('span')[1]->textContent,
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
        foreach ($object as $node) {
            $data[] = [
                'fullsize' => $node->getAttribute('href'),
                'thumb' => $node->getElementsByTagName('img')[0]->getAttribute('src'),
            ];
        }
        return $data;
    }

    public function pageQuery(int $page, string $url)
    {
        $page = $page * 30;
        $pageQuery = '';

        if (strpos($url, '?') !== false) {
            $pageQuery = '&p=';
        } else {
            $pageQuery = '?p=';
        }

        $returnPage = $pageQuery . $page;

        return $returnPage;
    }

    public function urlBuild(string $regionSourceId = '', string $categorySourceId = '', string $keyword = '')
    {
        $domain   = self::$model->domain;
        $category = CategorySource::findOne($categorySourceId);

        $keyword = urlencode(iconv('utf-8', 'windows-1251//IGNORE', str_replace(' ', '+', $keyword)));

        $url = '';

        if ($categorySourceId && !$keyword) {
            $url = $this->processUrl($category->source_url);
        }

        if ($categorySourceId && $keyword && $category->source_category_alias) {
            $url = $domain . '/' . self::ACTION_SEARCH . $category->source_category_alias . '/?' . self::QUERY_KEYWORD . $keyword;
        }

        if (!$categorySourceId && $keyword) {
            $url = $domain . '/' . self::ACTION_SEARCH . '?' . self::QUERY_KEYWORD . $keyword;
        }

        return $url;
    }

}
