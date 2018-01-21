<?php

namespace backend\models\parser\source;

use backend\models\CategorySource;
use backend\models\History;
use backend\models\parser\Parser;
use backend\models\parser\ParserSourceInterface;
use backend\models\Source;

class KrasotkaproParser extends Parser implements ParserSourceInterface
{

    const ACTION_SEARCH  = 'search/?';
    const QUERY_CATEGORY = 'section=';
    const QUERY_KEYWORD  = 'q=';

    // const XPATH_WARNING = '//div[contains(@class, \'main-column\')]/section/div[@style]'; // At Catalog/Search Page
    const XPATH_CATALOG = '//div[contains(@class, \'catalog-item\')]//a[@onclick]'; // At Catalog/Search Page
    // const XPATH_CATALOG = '//div[contains(@class, \'main-column\')]/section/div[contains(@class, \'catalog\')]//div[contains(@class, \'catalog-item\')]//a[@onclick]'; // At Catalog/Search Page

    // const XPATH_SUPER = '//notelement'; // At Product Page. JS Script with JSON Whole Data Object
    const XPATH_DESCRIPTION = '//div[contains(@itemprop, \'description\')]'; // At Product Page
    const XPATH_IMAGE = '//a[contains(@class, \'fancy-gallery\')]'; // At Product Page. Full size.
    const XPATH_ATTRIBUTE   = '//div[contains(@class, \'property\')]'; // At Product Page

    const LEVEL_ONE_CATEGORY_NODE    = '//div[contains(@id, \'section-list\')]/ul/li'; // At HomePage sidebar
    // const LEVEL_ONE_CATEGORY_NODE    = '//div[contains(@id, \'section-list\')]/ul/li[contains(@class, \'parent\')]'; // At HomePage sidebar
    const LEVEL_TWO_CATEGORY_NODE    = '//ul//a[@href]'; // At Level One Category Page leftmenu
    const LEVEL_CATEGORY_ALIAS       = '//div[contains(@data-fl-action, \'track-category-view\')]'; 

    const CURL_FOLLOW = 0; // CURLOPT_FOLLOWLOCATION

    // const MAX_QUANTITY = 990; // Real range is from 990 to ~1140 (+/-)



    public function parseCategories()
    {
        $response      = $this->curlSession(self::$model->domain);
        $levelOneNodes = $this->getNodes($response, self::LEVEL_ONE_CATEGORY_NODE);

        $data = $dataL0 = $dataL1 = [];

        foreach ($levelOneNodes as $key => $node) {

            if ($node->getAttribute('class') == 'top_category_item') {
                $data[$key] = [
                    'href'  => '',
                    'title' => $node->textContent,
                    'nest_level' => 0,
                ];
                $dataL0[] = $key;
            }

            if ($node->getAttribute('class') != 'top_category_item' && $node->parentNode->getAttribute('class') == 'left-menu') {

                $hrefMenu = trim ( $node->getElementsByTagName('a')[0]->getAttribute('href') );
                $titleMenu = trim ( $node->getElementsByTagName('a')[0]->textContent );
                
                $data[end($dataL0)]['children'][$key] = [
                    'href'  => self::$model->domain . $hrefMenu ,
                    'title' => $titleMenu != '' ? $titleMenu : '--' ,
                    'nest_level' => 1,
                ];

                $dataL1[] = $key;

                if (($uls = $node->getElementsByTagName('ul')) && $uls->length) {
                    foreach ($uls[0]->getElementsByTagName('a') as $link) {
                        $data[end($dataL0)]['children'][end($dataL1)]['children'][] = [
                            'href'  => self::$model->domain . $link->getAttribute('href'),
                            'title' => trim($link->textContent),
                            'nest_level' => 2,
                        ];
                    }
                }
            }
        }
        return $data;
    }


    // public function parseAlias($url)
    // {
    //     $response      = $this->curlSession($url);
    //     $aliasNodes = $this->getNodes($response, self::LEVEL_CATEGORY_ALIAS);
    //     foreach ($aliasNodes as $nodeAlias) {
    //         return trim ( $nodeAlias->getAttribute('data-fl-category-id') );
    //     }
    //     return '';
    // }
    
    // public function parseCategories()
    // {
    //     // LEVEL ONE Categories
    //     $response      = $this->curlSession(self::$model->domain);
    //     $levelOneNodes = $this->getNodes($response, self::LEVEL_ONE_CATEGORY_NODE);

    //     $data = [];

    //     foreach ($levelOneNodes as $node) {
    //         $hrefMenu = trim ( $node->getElementsByTagName('a')[0]->getAttribute('href') );
    //         $titleMenu = trim ( $node->getElementsByTagName('a')[0]->textContent );
            
    //         $levelOne = [
    //             'dump'  => '' ,
    //             'href'  => self::$model->domain . $hrefMenu ,
    //             'alias' => $this->parseAlias(self::$model->domain . $hrefMenu) ,
    //             'csid'  => '' ,
    //             'title' => $titleMenu != '' ? $titleMenu : '--' ,
    //         ];
            
    //         // LEVEL TWO Categories
    //         $levelTwoCurrent = array ( );
    //         $levelThreeCurrent = array ( );
    //         $levelTwoNodes = $this->getNodes($node->ownerDocument->saveHTML($node), self::LEVEL_TWO_CATEGORY_NODE);
    //         $level = 2;
    //         if ( $node->getElementsByTagName('ul')[0]->getAttribute('class') == 'alternative_ur2' ) {
    //             foreach ($levelTwoNodes as $nodeTwo) {
    //                 if ( $nodeTwo->getAttribute('class') == 'column_header' )
    //                     $level = 3;
    //                 else {
    //                     $hrefMenu = trim ( $nodeTwo->getAttribute('href') );
    //                     $titleMenu = trim ( iconv ( 'UTF-8' , 'ISO-8859-1' , $nodeTwo->textContent ) );
                        
    //                     if ( $level == 2 )
    //                         $levelTwoCurrent[] = [
    //                             'dump'  => '' ,
    //                             'href'  => self::$model->domain . $hrefMenu ,
    //                             'alias' => $this->parseAlias(self::$model->domain . $hrefMenu) ,
    //                             'csid'  => '' ,
    //                             'title' => $titleMenu != '' ? $titleMenu : '--' ,
    //                         ];
    //                     else 
    //                         $levelThreeCurrent[] = [
    //                             'dump'  => '' ,
    //                             'href'  => self::$model->domain . $hrefMenu ,
    //                             'alias' => $this->parseAlias(self::$model->domain . $hrefMenu) ,
    //                             'csid'  => '' ,
    //                             'title' => $titleMenu != '' ? $titleMenu : '--' ,
    //                         ];
    //                 }
    //             }
    //             if ( count ( $levelThreeCurrent ) > 0 )
    //                 $levelTwoCurrent[0]['children'] = $levelThreeCurrent;
    //         }
    //         else {
    //             foreach ($levelTwoNodes as $nodeTwo) {
    //                 $hrefMenu = trim ( $nodeTwo->getAttribute('href') );
    //                 $titleMenu = trim ( iconv ( 'UTF-8' , 'ISO-8859-1' , $nodeTwo->textContent ) );
                    
    //                 $levelTwoCurrent[] = [
    //                     'dump'  => '' ,
    //                     'href'  => self::$model->domain . $hrefMenu ,
    //                     'alias' => $this->parseAlias(self::$model->domain . $hrefMenu) ,
    //                     'csid'  => '' ,
    //                     'title' => $titleMenu != '' ? $titleMenu : '--' ,
    //                 ];
    //             }
    //         }
            
    //         $levelOne['children'] = $levelTwoCurrent;
            
    //         $data[] = $levelOne;
            
    //         if (count ( $data ) > 1) {
    //             break;
    //         }
    //     }
    //     return $data;
    // }

    /**
     * @return array
     */

    public function getWarningData(\DOMNodeList $nodes)
    {
        // foreach ($nodes as $node) {
        //     $data[] = $node->textContent;
        // }

        // return $data;
    }

    /**
     * Extracting data from the product item's element of a category/search page
     * @return array
     */

    // public function getProducts(\DOMNodeList $nodes)
    public function getProducts($nodes)
    {
        $data = [];

        // print_r($nodes);
        
        foreach ($nodes as $node) {
            $tmp = explode ( "', '" , str_replace ( array ( "ecommerceProductClick('" , "');" ) , array ( '' , '' ) , $node->getAttribute('onclick') ) );
            
            $id    = $tmp[0];
            $href  = $node->getAttribute('href');
            $name  = $tmp[1];
            $price = $tmp[2];

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
        // $script     = $nodes[0]->getElementsByTagName('script')[0]->nodeValue;
        // $explodeOne = explode('({', $script)[1];
        // $explodeTwo = explode('})', $explodeOne)[0];
        // $data       = json_decode('{' . $explodeTwo . '}');

        // return $data;
    }

    /**
     * Getting descriptions data from the object produced by getSuperData()
     * @return array
     */
    public function getDescriptionData($object)
    {
        $data = [];
        // if (isset($object->Description)) {
        //     foreach ($object->Description->Blocks as $descScope) {
        //         $data[] = [
        //             'title' => $descScope->Title,
        //             'text'  => $descScope->Text,
        //         ];
        //     }
        // } else {
            foreach ($object as $node) {
                $data[] = [
                    'title' => '',
                    'text'  => trim($node->textContent),
                ];
            }
        // }
        return $data;
    }

    /**
     * Getting attributes data from the object produced by getSuperData()
     * @return array
     */
    public function getAttributeData($object)
    {
        $data = [];
        // if (isset($object->Capabilities)) {
        //     foreach ($object->Capabilities->Capabilities as $attrScope) {
        //         $data[] = [
        //             'title' => $attrScope->Name,
        //             'value' => is_string($attrScope->Value) ? $attrScope->Value : $attrScope->Value[0]->Text,
        //         ];
        //     }
        // } else {
            foreach ($object as $node) {
                $data[] = [
                    'title' => trim($node->getElementsByTagName('span')[0]->textContent),
                    'value' => trim($node->getElementsByTagName('span')[1]->textContent),
                ];
            }
        // }
        return $data;
    }

    /**
     * Getting image data from the object produced by getSuperData()
     * @return array
     */
    public function getImageData($object)
    {
        $data = [];
        // if (isset($object->Gallery)) {
        //     foreach ($object->Gallery->Groups[0]->Elements as $imageScope) {
        //         $data[] = [
        //             'fullsize' => $imageScope->Original,
        //             'thumb'    => $imageScope->Preview,
        //         ];
        //     }
        // } else {
            foreach ($object as $node) {
                $data[] = [
                    'fullsize' => self::$model->domain . trim($node->getAttribute('href')),
                    'thumb'    => self::$model->domain . trim($node->getElementsByTagName('img')[0]->getAttribute('src')),
                ];
            }
        // }
        return $data;
    }

    public function pageQuery(int $page, string $url)
    {
        $pageQuery = '&q=!';
        return $page > 0 ? $pageQuery : '';
    }

    public function urlBuild(string $regionSourceId = '', string $categorySourceId = '', string $keyword = '')
    {
        $model    = self::$model;
        $domain   = self::$model->domain . '/';
        $category = CategorySource::findOne($categorySourceId);

        $keyword = str_replace(' ', '+', $keyword);

        $url = '';

        if ($categorySourceId != null && $keyword != '') {
            // if ($category->source_category_alias) {
            //     $url = $domain . self::ACTION_SEARCH . self::QUERY_CATEGORY . $category->source_category_alias . '&' . self::QUERY_KEYWORD . $keyword;
            // } else {
                $url = $this->processUrl($category->source_url) . '?' . self::QUERY_KEYWORD . $keyword;
            // }
        }

        if ($categorySourceId != null && $keyword == '') {
            // if ($category->source_category_alias) {
            //     $url = $domain . self::ACTION_SEARCH . self::QUERY_CATEGORY . $category->source_category_alias;
            // } else {
                $url = $this->processUrl($category->source_url);
            // }
        }

        if ($categorySourceId == null && $keyword != '') {
            $url = $domain . self::ACTION_SEARCH . '&' . self::QUERY_KEYWORD . $keyword;
        }

        return $url;
    }

}
