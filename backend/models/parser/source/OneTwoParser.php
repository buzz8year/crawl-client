<?php

namespace backend\models\parser\source;

use backend\models\CategorySource;
use backend\models\History;
use backend\models\parser\Parser;
use backend\models\parser\ParserSourceInterface;
use backend\models\Source;
use Yii;

class OneTwoParser extends Parser implements ParserSourceInterface
{

    const ACTION_SEARCH  = 'search/';
    const QUERY_CATEGORY = '';
    const QUERY_KEYWORD  = '?q=';

    const XPATH_WARNING = ''; // At Catalog/Search Page
    const XPATH_CATALOG = '//*[@data-product-id]'; // At Catalog/Search Page

    const XPATH_SUPER = '//div[@class=\'pc-main\']'; // At Product Page. JS Script with JSON Whole Data Object
    const XPATH_ATTRIBUTE   = './/div[@id=\'tab-char\']'; // At Product Page
    // const XPATH_DESCRIPTION = './/div[@id=\'tab-desc\']'; // At Product Page
    const XPATH_IMAGE = './/div[contains(@class, \'ss-slide\')]'; // At Product Page. Full size.ss-slide

    //const LEVEL_ONE_CATEGORY_NODE    = '//*[contains(@class, \'ol-menu-main-level\')]'; // At HomePage navmenu
    const LEVEL_ONE_CATEGORY_NODE    = '//*[contains(@class, \'catalog-menu\')]/div/ul/li'; // At HomePage navmenu
    // const LEVEL_SUB_CATEGORY_NODE    = './/span[@class=\'ol-menu-indent\']'; // At Level Two Category
    // const LEVEL_TWO_CATEGORY_NODE    = './/li[@class=\'ol-menu-second-stage-li\']'; // At Level Two Category
    // const LEVEL_THREE_CATEGORY_NODE  = '//div[@class=\'pb15\']'; // At Level Three Category
    // const LEVEL_TWO_CATEGORY_CLASS   = 'title'; // At Level One Category Page leftmenu
    // const LEVEL_THREE_CATEGORY_CLASS = 'acont'; // At Level One Category Page leftmenu

    const CURL_FOLLOW = 0; // CURLOPT_FOLLOWLOCATION

    const MAX_QUANTITY = 990; // Real range is from 990 to ~1140 (+/-)

    public function parseCategories()
    {
        $data = [];

        if ($response = $this->phantomSession(self::$model->domain)) {
            // print_r($response);
            if (($lisOne = $this->getNodes($response, self::LEVEL_ONE_CATEGORY_NODE)) && $lisOne->length) {
                // print_r($lisOne);
                foreach ($lisOne as $keyOne => $liOne) {
                    $linkOne = $liOne->getElementsByTagName('a')[0];
                    $data[$keyOne] = [
                        'csid'       => '',
                        'dump'       => '',
                        'alias'      => '',
                        'href'       => $this->processUrl($linkOne->getAttribute('href')),
                        'title'      => trim($linkOne->textContent),
                        'nest_level' => 0,
                    ];
                    if (($lisTwo = $liOne->getElementsByTagName('li')) && $lisTwo->length) {
                        foreach ($lisTwo as $keyTwo => $liTwo) {
                            $linkTwo = $liTwo->getElementsByTagName('a')[0];
                            $data[$keyOne]['children'][$keyTwo] = [
                                'csid'       => '',
                                'dump'       => '',
                                'alias'      => '',
                                'href'       => $this->processUrl($linkTwo->getAttribute('href')),
                                'title'      => trim($linkTwo->textContent),
                                'nest_level' => 1,
                            ];

                            if ($resp = $this->curlSession($this->processUrl($linkTwo->getAttribute('href')))) {
                                if (($subsThree = $this->getNodes($resp, '//div[contains(@class, \'subcategory\')]')) && $subsThree->length) {
                                    foreach ($subsThree as $liThree) {
                                        if ($liThree->parentNode->nodeName == 'a') {
                                            if ($linkThree = $liThree->parentNode->getAttribute('href')) {
                                                $title = '';
                                                foreach ($liThree->getElementsByTagName('div') as $div) {
                                                    if ($div->getAttribute('class') == 'title') {
                                                        $title = trim($div->textContent);
                                                    }
                                                }
                                                $data[$keyOne]['children'][$keyTwo]['children'][] = [
                                                    'csid'       => '',
                                                    'dump'       => '',
                                                    'alias'      => '',
                                                    'href'       => $this->processUrl($linkThree),
                                                    'title'      => $title,
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
            }
        }

        return $data;
    }

    // public function parseCategories()
    // {
    //     $response      = $this->curlSession(self::$model->domain);
    //     Yii::trace($response, 'my');

    //     $search = array("<!--", "-->");
    //     $replace = array("", "");
    //     $response = str_replace($search, $replace, $response);
    //     $dom                     = new \DOMDocument();
    //     $dom->formatOutput       = true;
    //     $dom->preserveWhiteSpace = false;
    //     @$dom->loadHTML($response);
    //     $xpath = new \DOMXPath($dom);
    //     $nodes = $xpath->query(self::LEVEL_ONE_CATEGORY_NODE);
    //     $levelOneData = array();
    //     $levelOneNodes = $nodes[0]->childNodes;
    //     $data = [];

    //     // LEVEL ONE Categories

    //     foreach ($levelOneNodes as $nodeKey => $nodeOne) {
    //         Yii::trace("Test", 'my');


    //         $li = $nodeOne->getElementsByTagName('ul')[0]->childNodes;
    //         foreach ($li as $tempKey => $liLines) {
    //             $liLine = $liLines->childNodes;
    //             $levelOneData = [
    //                 'href' => self::$model->domain . $liLine[0]->getAttribute('href'),
    //                 'title' => trim($liLine[0]->textContent),
    //             ];
    //             if (!is_null($liLine[1])) {
    //                 $subLiLines = $liLine[1]->getElementsByTagName('li');
    //             foreach ($subLiLines as $subLiLine) {
    //                 $levelTwoData = [
    //                     'href' => self::$model->domain . $subLiLine->getElementsByTagName('a')[0]->getAttribute('href'),
    //                     'title' => trim($subLiLine->getElementsByTagName('a')[0]->textContent),
    //                 ];
    //                 $subUrl = self::$model->domain . $subLiLine->getElementsByTagName('a')[0]->getAttribute('href');
    //                 $res = $this->curlSession($subUrl);
    //                 $search = array("<!--", "-->");
    //                 $replace = array("", "");
    //                 $res = str_replace($search, $replace, $res);
    //                 $dom = new \DOMDocument();
    //                 $dom->formatOutput = true;
    //                 $dom->preserveWhiteSpace = false;
    //                 @$dom->loadHTML($res, LIBXML_HTML_NOIMPLIED);
    //                 $xpath = new \DOMXPath($dom);
    //                 $subNodes = $xpath->query(self::LEVEL_THREE_CATEGORY_NODE);
    //                 foreach ($subNodes as $subNode) {
    //                     $subNodeLines = $subNode->getElementsByTagName('a');
    //                     foreach ($subNodeLines as $subNodeLine) {
    //                         $levelThreeData = [
    //                             'href' => self::$model->domain . $subNodeLine->getAttribute('href'),
    //                             'title' => trim($subNodeLine->getElementsByTagName('div')[3]->textContent),
    //                         ];

    //                     }
    //                 }
    //                 // LEVEL TWO Nesting
    //                 if (isset($levelThreeData)) {
    //                     $levelTwoData['children'][] = $levelThreeData;
    //                 }
    //             }
    //         }
    //             // LEVEL ONE Nesting
    //             $levelOneData['children'][] = $levelTwoData;


    //             // if ($tempKey == 2) {
    //             //     break;
    //             // }
    //             // Do not forget to REMOVE

    //         }
    //         // if ($nodeKey == 2) {
    //         //     break;
    //         // }
    //     }

    //     $data[] = $levelOneData;
    //     return $data;
    // }

    /**
     * @return array
     */

    public function getWarningData(\DOMNodeList $nodes)
    {
        foreach ($nodes as $node) {
            $data[] = $node->textContent;
        }

        return $data;
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
                if ($element->getAttribute('class')) {
                    if ($element->getAttribute('class') == 'title') {
                        $href = $this->processUrl($element->getAttribute('href'));
                        $title = trim($element->textContent);
                    }
                    if ($element->getAttribute('class') == 'price') {
                        if ($element->getElementsByTagName('span')->length) {
                            $price = preg_replace('/[^0-9]/', '', $element->getElementsByTagName('span')[0]->textContent);
                        }
                    }
                }
            }
            if ($href && $price) {
                $data[] = [
                    'name'       => $title,
                    'price'      => $price,
                    'href'       => $href,
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

        $data [] = [
            'title' => '', 
            'text' => $object[0]->textContent,
        ];

        return $data;
    }

    /**
     *
     * @return array
     */
    public function getAttributeData($object)
    {
        $data = [];

        $child = $object[0]->childNodes;
        $nodes = $child[0]->childNodes;
        foreach ($nodes as $node) {
            $text = $node->getElementsByTagName('div')[0]->textContent;
            $value = $node->getElementsByTagName('div')[1]->textContent;
            $data [] = array('title' => $text, 'value'=> $value);
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

        foreach ($object as $imgLine) {
            $data[] = array('fullsize'=> $imgLine->getElementsByTagName('img')[0]->getAttribute('src'), 'thumb' => '');
        }

        return $data;
    }

    public function pageQuery(int $page, string $url)
    {
        $pageQuery = '';

        if (strpos($url, 'context') !== false) {
            $pageQuery = '&page=';
        } elseif (strpos($url, '?') !== false) {
            $pageQuery = '&page=';
        } else {
            $pageQuery = '?page=';
        }

        return $page > 0 ? $pageQuery . $page : '';
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
