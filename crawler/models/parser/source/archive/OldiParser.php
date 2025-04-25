<?php

namespace crawler\models\parser\source;

use crawler\models\CategorySource;
use crawler\models\History;
use crawler\models\parser\Parser;
use crawler\models\parser\ParserSourceInterface;
use crawler\models\source\Source;
use Yii;

class OldiParser extends Parser implements ParserSourceInterface
{

    const ACTION_SEARCH  = 'search/';
    const QUERY_CATEGORY = '';
    const QUERY_KEYWORD  = '?q=';

    const XPATH_WARNING = ''; // At Catalog/Search Page
    // const XPATH_CATALOG = '//*[contains(@class, \'smallparamscont\')]'; // At Catalog/Search Page
    const XPATH_CATALOG = '//div[@data-vid]'; // At Catalog/Search Page

    // const XPATH_SUPER = '//div[@class=\'prod-main-cont\']'; // At Product Page. JS Script with JSON Whole Data Object
    const XPATH_SUPER = ''; // At Product Page. JS Script with JSON Whole Data Object
    const XPATH_ATTRIBUTE   = '//table[@class=\'characts-list\']//tr'; // At Product Page
    const XPATH_DESCRIPTION = '//div[@id=\'detail-text\']//div[@itemprop=\'description\']'; // At Product Page
    const XPATH_IMAGE = '//div[@class=\'promoitem\']'; // At Product Page. Full size.

    //const LEVEL_ONE_CATEGORY_NODE    = '//*[contains(@class, \'ol-menu-main-level\')]'; // At HomePage navmenu
    const LEVEL_ONE_CATEGORY_NODE    = '//ul[@class=\'sitemapcat\']/li'; // At HomePage navmenu
    // const LEVEL_SUB_CATEGORY_NODE    = './/span[@class=\'ol-menu-indent\']'; // At Level Two Category
    // const LEVEL_TWO_CATEGORY_NODE    = './/li[@class=\'ol-menu-second-stage-li\']'; // At Level Two Category
    // const LEVEL_THREE_CATEGORY_NODE  = './/li[@class=\'ol-menu-third-stage-li\']'; // At Level Three Category
    // const LEVEL_TWO_CATEGORY_CLASS   = 'title'; // At Level One Category Page leftmenu
    // const LEVEL_THREE_CATEGORY_CLASS = 'acont'; // At Level One Category Page leftmenu

    const CURL_FOLLOW = 0; // CURLOPT_FOLLOWLOCATION

    const MAX_QUANTITY = 990; // Real range is from 990 to ~1140 (+/-)


    public function parseCategories()
    {
        $data = [];

        if ($response = $this->curlSession(self::$model->domain . '/catalog/all/')) {
            if (($nodes = $this->getNodes($response, self::LEVEL_ONE_CATEGORY_NODE)) && $nodes->length) {
                foreach ($nodes as $key => $node) {
                    foreach ($node->getElementsByTagName('a') as $link) {
                        if ($link->getAttribute('href')) {

                            $trim = trim($link->getAttribute('href'), '/');
                            $exp  = explode('/', $trim);

                            if ($link->parentNode === $node) {
                                $data[$key] = [
                                    'csid'       => $exp[1],
                                    'dump'       => '',
                                    'alias'      => '',
                                    'href'       => self::$model->domain . $link->getAttribute('href'),
                                    'title'      => trim($link->textContent),
                                    'nest_level' => 0,
                                ];
                            }
                            elseif ($link->parentNode->parentNode->parentNode === $node) {
                                $data[$key]['children'][$exp[1]] = [
                                    'csid'       => $exp[1],
                                    'dump'       => '',
                                    'alias'      => '',
                                    'href'       => self::$model->domain . $link->getAttribute('href'),
                                    'title'      => trim($link->textContent),
                                    'nest_level' => 1,
                                ];
                                if (($uls = $link->parentNode->getElementsByTagName('ul')) && $uls->length) {
                                    foreach ($uls[0]->getElementsByTagName('a') as $lastLink) {

                                        $trimLast = trim($lastLink->getAttribute('href'), '/');
                                        $expLast  = explode('/', $trimLast);

                                        $data[$key]['children'][$exp[1]]['children'][] = [
                                            'csid'       => $expLast[1],
                                            'dump'       => '',
                                            'alias'      => '',
                                            'href'       => self::$model->domain . $lastLink->getAttribute('href'),
                                            'title'      => trim($lastLink->textContent),
                                            'nest_level' => 2,
                                        ];
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

    // public function parseCategories()
    // {
    //     $response = $this->curlSession(self::$model->domain);
    //     $search = array("<nav", "</nav>");
    //     $replace = array("<div", "</div>");
    //     $html = str_replace($search, $replace, $response);
    //     $posBegin = strpos($html, "<!-- begin of yandex market -->");
    //     $posEnd = strpos($html, "<!-- //Rating@Mail.ru counter -->");
    //     $response = substr($html, 0, $posBegin).substr($html, $posEnd, -1);
    //     $dom                     = new \DOMDocument();
    //     $dom->formatOutput       = true;
    //     $dom->preserveWhiteSpace = false;
    //     @$dom->loadHTML($response);
    //     $xpath = new \DOMXPath($dom);
    //     $nodes = $xpath->query(self::LEVEL_ONE_CATEGORY_NODE);
    //     //Yii::trace(var_dump($nodes), 'my');
    //     //return $nodes;
    //     //$levelOneNodes = $this->getNodes($response, self::LEVEL_ONE_CATEGORY_NODE);
    //     $levelOneNodes = $nodes;
    //     $data = [];

    //     // LEVEL ONE Categories

    //     foreach ($levelOneNodes as $tempKey => $nodeOne) {
    //         Yii::trace("Test", 'my');
    //         $levelOneData = [
    //             'href'  => self::$model->domain . $nodeOne->getElementsByTagName('a')[0]->getAttribute('href'),
    //             //'alias' => $nodeOne->getAttribute('data-ext-menu'),
    //             'title' => trim($nodeOne->getElementsByTagName('a')[0]->getElementsByTagName('div')[0]->textContent),
    //         ];
    //         $subNodes = $xpath->query(self::LEVEL_SUB_CATEGORY_NODE, $nodeOne);
    //         foreach ($subNodes as $subNode) {
    //             $twoNodes = $xpath->query(self::LEVEL_TWO_CATEGORY_NODE, $subNode);
    //             foreach ($twoNodes as $twoNode) {
    //                 $levelTwoData = [
    //                     'href'  => self::$model->domain . $twoNode->getElementsByTagName('a')[0]->getAttribute('href'),
    //                     'title' => trim($twoNode->getElementsByTagName('a')[0]->textContent),
    //                 ];
    //             }
    //             $threeNodes = $xpath->query(self::LEVEL_THREE_CATEGORY_NODE, $subNode);
    //             foreach ($threeNodes as $threeNode) {
    //                 $levelThreeData = [
    //                     'href'  => self::$model->domain . $threeNode->getElementsByTagName('a')[0]->getAttribute('href'),
    //                     'title' => trim($threeNode->getElementsByTagName('a')[0]->textContent),
    //                 ];
    //             }
    //             if (isset($levelThreeData)) {
    //                 $levelTwoData['children'][] = $levelThreeData;
    //             }
    //         }

    //         // LEVEL ONE Nesting
    //         $levelOneData['children'][] = $levelTwoData;


    //         // if ($tempKey == 4) {
    //         //     break;
    //         // }
    //         // Do not forget to REMOVE
    //     }
    //     $data[] = $levelOneData;
    //     return $data;
    // }

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
        $extend = ' and (.//span[contains(@class, \'catalog-list-label-gift\')])';
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
            foreach ($node->getElementsByTagName('a') as $link) {
                if ($link->getAttribute('itemprop') && $link->getAttribute('itemprop') == 'url') {
                    $href = $link->getAttribute('href');
                    $name = trim($link->textContent);
                }
            }
            foreach ($node->getElementsByTagName('meta') as $meta) {
                if ($meta->getAttribute('itemprop') && $meta->getAttribute('itemprop') == 'price') {
                    $price = $meta->getAttribute('content');
                }
            }
            if ($href && $price) {
                $data[] = [
                    'price'      => $price,
                    'name'       => $name,
                    'href'       => self::$model->domain . $href,
                ];
            }
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
     *
     * @return array
     */
    public function getDescriptionData($object)
    {
        $data = [];
        foreach ($object as $node) {
            $data[] = [
                'title' => '', 
                'text' => $node->textContent,
            ];
        }
        return $data;
    }

    /**
     *
     * @return array
     */
    public function getAttributeData($object)
    {
        $data = [];

        foreach ($object as $tr) {
            if ($tr->getElementsByTagName('td') && $tr->getElementsByTagName('td')->length == 2) {
                $data [] = [
                    'title' => $tr->getElementsByTagName('td')[0]->textContent, 
                    'value' => $tr->getElementsByTagName('td')[1]->textContent
                ];
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
        foreach ($object as $img) {
            $data[] = [
                'fullsize' => self::$model->domain . $img->getElementsByTagName('a')[0]->getAttribute('href'), 
                'thumb'    => self::$model->domain . $img->getElementsByTagName('img')[0]->getAttribute('src'),
            ];
        }
        return $data;
    }

    public function pageQuery(int $page, string $url)
    {
        $pageQuery = '';

        $page++;

        if (strpos($url, '?') !== false) {
            $pageQuery = '&PAGEN_1=';
        } else {
            $pageQuery = '?PAGEN_1=';
        }

        return $page > 1 ? $pageQuery . $page : '';
    }

     public function urlBuild(string $regionSourceId = '', string $categorySourceId = '', string $keyword = '')
    {
        $model    = self::$model;
        $domain   = self::$model->domain . '/';
        $category = CategorySource::findOne($categorySourceId);

        $keyword = str_replace(' ', '+', $keyword);

        $url = '';

        if ($categorySourceId != null && $keyword != '') {
            if ($category->source_category_alias) {
                $url = $domain . self::ACTION_SEARCH . self::QUERY_CATEGORY . $category->source_category_alias . '&' . self::QUERY_KEYWORD . $keyword;
            } else {
                $url = $category->source_url . '?' . self::QUERY_KEYWORD . $keyword;
            }
        }

        if ($categorySourceId != null && $keyword == '') {
            if ($category->source_category_alias) {
                $url = $domain . self::ACTION_SEARCH . self::QUERY_CATEGORY . $category->source_category_alias;
            } else {
                $url = $category->source_url;
            }
        }

        if ($categorySourceId == null && $keyword != '') {
            $url = $domain . self::ACTION_SEARCH . self::QUERY_KEYWORD . $keyword;
        }

        return $url;
    }

}
