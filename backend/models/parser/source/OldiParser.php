<?php

namespace backend\models\parser\source;

use backend\models\CategorySource;
use backend\models\History;
use backend\models\parser\Parser;
use backend\models\parser\ParserSourceInterface;
use backend\models\Source;
use Yii;

class OldiParser extends Parser implements ParserSourceInterface
{

    const ACTION_SEARCH  = 'search/';
    const QUERY_CATEGORY = '';
    const QUERY_KEYWORD  = '?q=';

    const XPATH_WARNING = ''; // At Catalog/Search Page
    const XPATH_CATALOG = '//*[contains(@class, \'smallparamscont\')]'; // At Catalog/Search Page

    const XPATH_SUPER = '//div[@class=\'prod-main-cont\']'; // At Product Page. JS Script with JSON Whole Data Object
    const XPATH_ATTRIBUTE   = './/table[@class=\'characts-list\']'; // At Product Page
    const XPATH_DESCRIPTION = './/div[@id=\'detail-text\']'; // At Product Page
    const XPATH_IMAGE = './/div[@class=\'promoitem\']'; // At Product Page. Full size.

    //const LEVEL_ONE_CATEGORY_NODE    = '//*[contains(@class, \'ol-menu-main-level\')]'; // At HomePage navmenu
    const LEVEL_ONE_CATEGORY_NODE    = '//li[@class=\'ol-menu-main-level\']'; // At HomePage navmenu
    const LEVEL_SUB_CATEGORY_NODE    = './/span[@class=\'ol-menu-indent\']'; // At Level Two Category
    const LEVEL_TWO_CATEGORY_NODE    = './/li[@class=\'ol-menu-second-stage-li\']'; // At Level Two Category
    const LEVEL_THREE_CATEGORY_NODE  = './/li[@class=\'ol-menu-third-stage-li\']'; // At Level Three Category
    const LEVEL_TWO_CATEGORY_CLASS   = 'title'; // At Level One Category Page leftmenu
    const LEVEL_THREE_CATEGORY_CLASS = 'acont'; // At Level One Category Page leftmenu

    const CURL_FOLLOW = 0; // CURLOPT_FOLLOWLOCATION

    const MAX_QUANTITY = 990; // Real range is from 990 to ~1140 (+/-)


    public function parseCategories()
    {
        $response      = $this->curlSession(self::$model->domain);
        $search = array("<nav", "</nav>");
        $replace = array("<div", "</div>");
        $html = str_replace($search, $replace, $response);
        $posBegin = strpos($html, "<!-- begin of yandex market -->");
        $posEnd = strpos($html, "<!-- //Rating@Mail.ru counter -->");
        $response = substr($html, 0, $posBegin).substr($html, $posEnd, -1);
        $dom                     = new \DOMDocument();
        $dom->formatOutput       = true;
        $dom->preserveWhiteSpace = false;
        @$dom->loadHTML($response);
        $xpath = new \DOMXPath($dom);
        $nodes = $xpath->query(self::LEVEL_ONE_CATEGORY_NODE);
        //Yii::trace(var_dump($nodes), 'my');
        //return $nodes;
        //$levelOneNodes = $this->getNodes($response, self::LEVEL_ONE_CATEGORY_NODE);
        $levelOneNodes = $nodes;
        $data = [];

        // LEVEL ONE Categories

        foreach ($levelOneNodes as $tempKey => $nodeOne) {
            Yii::trace("Test", 'my');
            $levelOneData = [
                'href'  => self::$model->domain . $nodeOne->getElementsByTagName('a')[0]->getAttribute('href'),
                //'alias' => $nodeOne->getAttribute('data-ext-menu'),
                'title' => trim($nodeOne->getElementsByTagName('a')[0]->getElementsByTagName('div')[0]->textContent),
            ];
            $subNodes = $xpath->query(self::LEVEL_SUB_CATEGORY_NODE, $nodeOne);
            foreach ($subNodes as $subNode) {
                $twoNodes = $xpath->query(self::LEVEL_TWO_CATEGORY_NODE, $subNode);
                foreach ($twoNodes as $twoNode) {
                    $levelTwoData = [
                        'href'  => self::$model->domain . $twoNode->getElementsByTagName('a')[0]->getAttribute('href'),
                        'title' => trim($twoNode->getElementsByTagName('a')[0]->textContent),
                    ];
                }
                $threeNodes = $xpath->query(self::LEVEL_THREE_CATEGORY_NODE, $subNode);
                foreach ($threeNodes as $threeNode) {
                    $levelThreeData = [
                        'href'  => self::$model->domain . $threeNode->getElementsByTagName('a')[0]->getAttribute('href'),
                        'title' => trim($threeNode->getElementsByTagName('a')[0]->textContent),
                    ];
                }
                if (isset($levelThreeData)) {
                    $levelTwoData['children'][] = $levelThreeData;
                }
            }

            // LEVEL ONE Nesting
            $levelOneData['children'][] = $levelTwoData;


            // if ($tempKey == 4) {
            //     break;
            // }
            // Do not forget to REMOVE
        }
        $data[] = $levelOneData;
        return $data;
    }

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
            $id = trim($node->getElementsByTagName('ins')[1]->getAttribute('data-id'));
            $href = $node->getElementsByTagName('a')[0]->getAttribute('href');
            $name = trim($node->getElementsByTagName('div')[4]->textContent);
            $price = floatval(str_replace(" ","",$node->getElementsByTagName('div')[7]->nodeValue));
            if ($href) {
                $data[] = [
                    'id'         => $id,
                    'name'       => $name,
                    'price'      => $price,
                    'href'       => self::$model->domain . $href,
                    'attributes' => [],
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
        if (isset($object->Description)) {
            foreach ($object->Description->Blocks as $descScope) {
                $data[] = [
                    'title' => $descScope->Title,
                    'text'  => $descScope->Text,
                ];
            }
        } else {
            $pLines = $object[0]->getElementsByTagName('p');

            foreach ($pLines as $pLine) {
                $data [] = array ("title" => '', 'Text' => $pLine->textContent);
            }
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

        if (isset($object->Capabilities)) {
            foreach ($object->Capabilities->Capabilities as $attrScope) {
                $data[] = [
                    'title' => $attrScope->Name,
                    'value' => is_string($attrScope->Value) ? $attrScope->Value : $attrScope->Value[0]->Text,
                ];
            }
        } else {
            $trLines = $object[0]->getElementsByTagName('tr');
                foreach ($trLines as $trLine) {
                    $data [] = array('title' => $trLine->getElementsByTagName('td')[0]->textContent, 'value'=> $trLine->getElementsByTagName('td')[1]->textContent);
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
        if (isset($object->Gallery)) {
            foreach ($object->Gallery->Groups[0]->Elements as $imageScope) {
                $data[] = [
                    'fullsize' => $imageScope->Original,
                    'thumb'    => $imageScope->Preview,
                ];
            }
        } else {
            foreach ($object as $imgLine) {
                $data[] = array('fullsize'=> $imgLine->getElementsByTagName('a')[0]->getAttribute('href'), 'thumb' => $imgLine->getElementsByTagName('img')[0]->getAttribute('src'));
            }

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
