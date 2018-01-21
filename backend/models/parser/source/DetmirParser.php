<?php

namespace backend\models\parser\source;

use backend\models\CategorySource;
use backend\models\History;
use backend\models\parser\Parser;
use backend\models\parser\ParserSourceInterface;
use backend\models\Source;

class DetmirParser extends Parser implements ParserSourceInterface
{

    const ACTION_SEARCH  = 'search/results/?product_category=0&type=product';
    const QUERY_CATEGORY = '';
    const QUERY_KEYWORD  = 'qt=';

    // const XPATH_WARNING = '//div[contains(@class, \'notfound_text\')]'; // At Catalog/Search Page
    const XPATH_CATALOG = '//div[@data-code]'; // At Catalog/Search Page
    
    const XPATH_BREADCRUMBS = '//div[contains(@class, \'breadcrumb\')]//a[contains(@itemprop, \'url\')]'; // At Catalog/Search Page

    const XPATH_SUPER = '//notelement'; // At Product Page. JS Script with JSON Whole Data Object
    const XPATH_DESCRIPTION = '//div[contains(@class, \'igh_inner\')]'; // At Product Page
    const XPATH_IMAGE = '//meta[contains(@name, \'og:image\')]'; // At Product Page. Full size.
    const XPATH_ATTRIBUTE   = '//div[contains(@class, \'ijh_inner\')]//p'; // At Product Page

    const LEVEL_ONE_CATEGORY_NODE    = '//div[contains(@class, \'main_menu_item_wrap\')]'; // At HomePage sidebar
    const LEVEL_TWO_CATEGORY_NODE    = '//div[contains(@class, \'drop_menu_lvl_1\')]//li[@data-id]'; // At Level One Category Page leftmenu
    const LEVEL_THREE_CATEGORY_NODE  = '//div[contains(@class, \'drop_menu_lvl_2\')]//ul'; 

    const CURL_FOLLOW = 0; // CURLOPT_FOLLOWLOCATION

    // const MAX_QUANTITY = 990; // Real range is from 990 to ~1140 (+/-)

    
    public function parseCategories()
    {
        // LEVEL ONE Categories
        $response      = $this->curlSession(self::$model->domain);
        $levelOneNodes = $this->getNodes($response, self::LEVEL_ONE_CATEGORY_NODE);

        $data = [];

        foreach ($levelOneNodes as $node) {
            $hrefMenu = trim ( str_replace ( 'http://' , 'https://' , $node->getElementsByTagName('a')[0]->getAttribute('href') ) );
            $titleMenu = trim ( $node->getElementsByTagName('a')[0]->textContent );
            $idMenu = trim ( $node->getElementsByTagName('a')[0]->getAttribute('data-num') );
            
            if ( $hrefMenu[0] == '/' )
                $hrefMenu = self::$model->domain . $hrefMenu;
            
            $data[$idMenu] = [
                'href'  => $hrefMenu ,
                'alias' => md5 ( $hrefMenu ) ,
                'title' => $titleMenu != '' ? $titleMenu : '--' ,
                'nest_level' => 0,
            ];
            
            // LEVEL TWO Categories
            $levelTwoNodes = $this->getNodes($node->ownerDocument->saveHTML($node), self::LEVEL_TWO_CATEGORY_NODE);
            foreach ($levelTwoNodes as $nodeTwo) {
                $hrefMenu = trim ( str_replace ( 'http://' , 'https://' , $nodeTwo->getElementsByTagName('a')[0]->getAttribute('href') ) );
                $titleMenu = iconv ( 'UTF-8' , 'ISO-8859-1' , trim ( $nodeTwo->getElementsByTagName('a')[0]->textContent ) );
                $idMenuTwo = trim ( $nodeTwo->getAttribute('data-id') );
                
                if ( $hrefMenu[0] == '/' )
                    $hrefMenu = self::$model->domain . $hrefMenu;
                
                $data[$idMenu]['children'][$idMenuTwo] = [
                    'href'  => $hrefMenu ,
                    'alias' => md5 ( $hrefMenu ) ,
                    'title' => $titleMenu != '' ? $titleMenu : '--' ,
                    'nest_level' => 1,
                ];
            }
            
            // LEVEL THREE Categories
            $levelThreeNodes = $this->getNodes($node->ownerDocument->saveHTML($node), self::LEVEL_THREE_CATEGORY_NODE);
            foreach ($levelThreeNodes as $nodeThree) {
                $parentCategory = trim ( str_replace ( 'drop_menu_list_lvl_2 ic' , '' , $nodeThree->getAttribute('class') ) );
                
                $links = $nodeThree->getElementsByTagName('a');
                foreach ($links as $link) {
                    $hrefMenu = trim ( str_replace ( 'http://' , 'https://' , $link->getAttribute('href') ) );
                    $titleMenu = iconv ( 'UTF-8' , 'ISO-8859-1' , trim ( $link->textContent ) );
                    
                    if ( $hrefMenu[0] == '/' )
                        $hrefMenu = self::$model->domain . $hrefMenu;
                    
                    $data[$idMenu]['children'][$parentCategory]['children'][] = [
                        'href'  => $hrefMenu ,
                        'alias' => md5 ( $hrefMenu ) ,
                        'title' => $titleMenu != '' ? $titleMenu : '--' ,
                        'nest_level' => 2,
                    ];
                }
            }
            
            // if (count ( $data ) > 2) {
            //     break;
            // }
        }
        return $data;
    }

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

    public function getProducts($nodes)
    {
        $data = [];
        
        foreach ($nodes as $node) {
            if (($links = $node->getElementsByTagName('a')) && $links->length) {
                $href = $links[1];
            }
            foreach ($node->getElementsByTagName('*') as $element) {
                if ($element->getAttribute('itemprop') && $element->getAttribute('itemprop') == 'price') {
                    $price = $element->textContent;
                }
            }
            if (isset($href, $price)) {
                $data[] = [
                    'name'       => $href->textContent,
                    'price'      => $price,
                    'href'       => self::$model->domain . $href->getAttribute('href'),
                ];
            }
        }

        return $data;
    }

    // public function getProducts($nodes)
    // {
    //     $data = [];

    //     $category = CategorySource::find()->where(['source_id' => $_GET['id'],'category_id'=>$_GET['cat']])->one();
        
    //     foreach ($nodes as $node) {
    //         $id    = $node->getAttribute('data-code');
    //         $link  = $node->getElementsByTagName('a')[0]->getAttribute('href');
    //         $name  = trim ( $node->getElementsByTagName('a')[0]->getAttribute('title') );
    //         $price = false;
            
    //         $spans = $node->getElementsByTagName('span');
    //         foreach ($spans as $span) {
    //             if ( $span->getAttribute('itemprop') == 'price' )
    //                 $price = $span->textContent;
    //         }

    //         if ($href&&$price) {
    //             $href  = self::$model->domain . $link;
    //             $response = $this->curlSession($href);
    //             if ( $response != '' ) {
    //                 $nodeProducts = $this->getNodes($response, self::XPATH_BREADCRUMBS);
                    
    //                 $addProd = false;
    //                 foreach ($nodeProducts as $nodeProduct) {
    //                     $breadlink = trim ( str_replace ( 'http://' , 'https://' , $nodeProduct->getAttribute('href') ) );
    //                     if ( $breadlink[0] == '/' )
    //                         $breadlink = self::$model->domain . $breadlink;
                        
    //                     if ( md5 ( $breadlink ) == $category->source_category_alias ) 
    //                         $addProd = true;
    //                 }
    //                 if ($addProd) {
    //                     $data[] = [
    //                         'id'         => $id,
    //                         'name'       => $name,
    //                         'price'      => $price,
    //                         'href'       => $href,
    //                         'attributes' => [],
    //                     ];
    //                 }
    //             }
    //         }
    //     }
    //     return $data;
    // }

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
                $tmp = explode ( ':' , $node->textContent );
                if ( !in_array ( $tmp[0] , array ( 'Êîä òîâàðà' , 'Àðòèêóë' , 'Áðåíä' ) ) ) {
                    $data[] = [
                        'title' => trim($tmp[0]),
                        'value' => trim($tmp[1]),
                    ];
                }
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
                    'fullsize' => trim($node->getAttribute('content')),
                    'thumb'    => '',
                ];
            }
        // }
        return $data;
    }

    public function pageQuery(int $page, string $url)
    {
        $page++;
        // if (strpos($url, '?') !== false) {
        //     $pageQuery = '&p=';
        // } else {
            $pageQuery = 'page/';
        // }
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
            // if ($category->source_category_alias) {
            //     $url = $domain . self::ACTION_SEARCH . self::QUERY_KEYWORD . $keyword;
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
