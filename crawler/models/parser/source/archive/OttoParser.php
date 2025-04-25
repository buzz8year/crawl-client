<?php

namespace crawler\models\parser\source;

use crawler\models\CategorySource;
use crawler\models\History;
use crawler\models\parser\Parser;
use crawler\models\parser\ParserSourceInterface;
use crawler\models\source\Source;

class OttoParser extends Parser implements ParserSourceInterface
{

    const ACTION_SEARCH  = '/q/?tr=t2&ms=1';
    const QUERY_CATEGORY = 'qc=';
    const QUERY_KEYWORD  = 'oq=';

    // const XPATH_WARNING = '//*[contains(@class, \'FLeft\')]//p'; // At Catalog/Search Page
    const XPATH_CATALOG = '//*[contains(@class, \'nmProduct\')]'; // At Catalog/Search Page

    const XPATH_SUPER = '//notelement'; // At Product Page. JS Script with JSON Whole Data Object
    const XPATH_DESCRIPTION = '//div[@id=\'ADSC2\']/div[contains(@class, \'ADSMidDef\')]'; // At Product Page
    // const XPATH_DESCRIPTION = '//div[contains(@class, \'ADSMidDef\')]/*[contains(@class, \'hSep\')]/p[contains(@class, \'glossarLinks\')]'; // At Product Page
    const XPATH_IMAGE = '//div[contains(@class, \'siglSld\')]'; // At Product Page. Full size.
    const XPATH_ATTRIBUTE   = '//notelement'; // At Product Page

    const CATEGORY_TREE_NODE = '//div[@class=\'sitemapMain\']/ul/li/h4';

    // const LEVEL_ONE_CATEGORY_NODE    = '//*[contains(@id, \'MainNavigation\')]//a[@href]'; // At HomePage sidebar
    // const LEVEL_TWO_CATEGORY_NODE    = '//*[contains(@id, \'NavLis\')]//a[@href]'; // At Level One Category Page leftmenu
    // const LEVEL_THREE_CATEGORY_NODE  = '//*[contains(@id, \'NavLis\')]//a[@href]'; // At Level One Category Page leftmenu
    // const LEVEL_CATEGORY_ALIAS       = '//link[contains(@rel, \'next\')]'; 

    const CURL_FOLLOW = 1; // CURLOPT_FOLLOWLOCATION

    // const MAX_QUANTITY = 990; // Real range is from 990 to ~1140 (+/-)


    /**
     * @return array
     */
    public function parseCategories()
    {
        $data = [];

        if ($response = $this->curlSession(self::$model->domain . '/service/sitemap/')) {
            if (($nodes = $this->getNodes($response, self::CATEGORY_TREE_NODE)) && $nodes->length) {
                // print_r($nodes);
                foreach ($nodes as $key => $node) {
                    $data[$key] = [
                        // 'href'       => self::$model->domain . $node->getAttribute('href'),
                        'href'       => '',
                        'title'      => trim($node->textContent),
                        'nest_level' => 0,
                    ];

                    if (($uls = $node->parentNode->getElementsByTagName('ul')) && $uls->length) {
                        foreach ($uls as $ul) {
                            // if (strpos($ul->getAttribute('class'), 'sitmaplvl_0') !== false) {
                            if (strpos($ul->getAttribute('class'), 'sitmaplvl_0') !== false) {
                                if ($tree = $this->nestCategories($ul)) {
                                    $data[$key]['children'] = $tree;
                                }
                                // break;
                            }
                        }
                    }

                    // if ($key == 1) {
                    //     break;
                    // }
                }
            }
        }

        // print_r($data);
        return $data;
    }

    /**
     * @return array
     */
    public function nestCategories($node, $nestLevel = 0)
    {   
        $tree = [];
        $nestLevel++;
        if (($children = $node->getElementsByTagName('li')) && $children->length) {
            foreach ($children as $key => $child) {
                if (($links = $child->getElementsByTagName('a')) && $links->length && trim($links[0]->textContent) && $links[0]->parentNode->parentNode === $node) {
                    $tree[$key] = [
                        'href'       => self::$model->domain . $links[0]->getAttribute('href'),
                        'title'      => trim($links[0]->textContent),
                        'nest_level' => $nestLevel,
                    ];

                    if (($uls = $child->getElementsByTagName('ul')) && $uls->length && $uls[0]->childNodes->length) {
                        $tree[$key]['children'] = $this->nestCategories($uls[0], $nestLevel);
                    }
                    unset($child);
                }
            }
        }
        return $tree;
    }

    // public function parseCategories()
    // {
    //     // LEVEL ONE Categories
    //     $response      = $this->curlSession(self::$model->domain);
    //     $levelOneNodes = $this->getNodes($response, self::LEVEL_ONE_CATEGORY_NODE);

    //     $data = [];

    //     foreach ($levelOneNodes as $node) {
    //         $hrefMenu = trim ( $node->getAttribute('href') );
    //         $titleMenu = trim ( $node->getElementsByTagName('span')[0]->textContent );
        
    //         // LEVEL TWO Categories
    //         $levelTwo      = array ( );
    //         $parentName    = $titleMenu . "&nbsp;&raquo;&nbsp;";
    //         $response      = $this->curlSession(self::$model->domain . $hrefMenu);
            
    //         $levelAlias = $this->getNodes($response, self::LEVEL_CATEGORY_ALIAS);
    //         foreach ($levelAlias as $alias) {
    //             $aliasMenu = str_replace ( array ( '/q/?c=' , '&p=1' ) , array ( '' , '' ) , trim ( $alias->getAttribute('href') ) );
    //         }
            
    //         $levelTwoNodes = $this->getNodes($response, self::LEVEL_TWO_CATEGORY_NODE);
    //         foreach ($levelTwoNodes as $nodeTwo) {
    //             if ( $nodeTwo->getAttribute('class') == 'MainActiv' ) {
    //                 $parentNameTwo = trim ( $nodeTwo->textContent ) . "&nbsp;";
    //             }
    //             else {
    //                 $hrefMenuTwo = trim ( $nodeTwo->getAttribute('href') );
    //                 $tmpTitleTwo = explode ( '&#32;' , trim ( $nodeTwo->textContent ) );
    //                 $titleMenuTwo = $parentName . $parentNameTwo . trim ( $tmpTitleTwo[0] );
                    
    //                 // LEVEL THREE Categories
    //                 $levelThree    = array ( );
    //                 $parentNameThree = $titleMenuTwo . "&nbsp;&raquo;&nbsp;";
    //                 $response      = $this->curlSession(self::$model->domain . $hrefMenuTwo);
                    
    //                 $levelAlias = $this->getNodes($response, self::LEVEL_CATEGORY_ALIAS);
    //                 foreach ($levelAlias as $alias) {
    //                     $aliasMenuTwo = str_replace ( array ( '/q/?c=' , '&p=1' ) , array ( '' , '' ) , trim ( $alias->getAttribute('href') ) );
    //                 }
                    
    //                 if ( count ( $tmpTitleTwo ) > 1 ) {
    //                     $levelThreeNodes = $this->getNodes($response, self::LEVEL_THREE_CATEGORY_NODE);
    //                     foreach ($levelThreeNodes as $nodeThree) {
    //                         if ( $nodeThree->getAttribute('class') != 'MainActiv' ) {
    //                             $hrefMenuThree = trim ( $nodeThree->getAttribute('href') );
    //                             $tmpTitleThree = explode ( '&#32;' , trim ( $nodeThree->textContent ) );
    //                             $titleMenuThree = $parentNameThree . trim ( $tmpTitleThree[0] );
                                
    //                             $response      = $this->curlSession(self::$model->domain . $hrefMenuThree);
    //                             $levelAlias = $this->getNodes($response, self::LEVEL_CATEGORY_ALIAS);
    //                             foreach ($levelAlias as $alias) {
    //                                 $aliasMenuThree = str_replace ( array ( '/q/?c=' , '&p=1' ) , array ( '' , '' ) , trim ( $alias->getAttribute('href') ) );
    //                             }
                                
    //                             $levelThree[] = [
    //                                 'dump'  => '' ,
    //                                 'href'  => self::$model->domain . $hrefMenuThree ,
    //                                 'alias' => $aliasMenuThree ,
    //                                 'csid'  => '' ,
    //                                 'title' => $titleMenuThree != '' ? $titleMenuThree : '--' ,
    //                             ];
    //                         }
    //                     }
    //                 }
                    
    //                 $levelTwo[] = [
    //                     'dump'  => '' ,
    //                     'href'  => self::$model->domain . $hrefMenuTwo ,
    //                     'alias' => $aliasMenuTwo ,
    //                     'csid'  => '' ,
    //                     'title' => $titleMenuTwo != '' ? $titleMenuTwo : '--' ,
    //                     'children' => $levelThree
    //                 ];
    //             }
    //         }
            
    //         $data[] = [
    //             'dump'  => '' ,
    //             'href'  => self::$model->domain . $hrefMenu ,
    //             'alias' => $aliasMenu ,
    //             'csid'  => '' ,
    //             'title' => $titleMenu != '' ? $titleMenu : '--' ,
    //             'children' => $levelTwo
    //         ];
            
    //         if (count ( $data ) > 0) {
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
     * @return
     */
    public static function xpathSale(string $xpath)
    {
        $extend = ' and .//div[@class=\'priceInfo\']/div[@class=\'stroke\']';
        $explode  = rtrim($xpath, ']');
        $xpath = $explode . $extend . ']';

        return $xpath;
    }





    /**
     * Extracting data from the product item's element of a category/search page
     * @return array
     */
    public function getProducts($nodes)
    {
        $data = [];

        // print_r($nodes);

        foreach ($nodes as $node) {
            $id    = $node->getAttribute('data-pid');
            $href  = explode ( '?' , $node->getAttribute('data-producturl') );
            $href  = $href[0];
            $name  = false;
            $price = false;
            
            $divs  = $node->getElementsByTagName('div');
            foreach ( $divs AS $div ) {
                switch ( $div->getAttribute('class') ) {
                    case 'artH1':
                        $name  = $div->textContent;
                    case 'price':
                    case 'price blackPrice':
                        $price = substr($div->textContent, 0, strpos( $div->textContent , '.' ) );
                }
            }

            if ($href&&$name&&$price) {
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
                    'title' => trim($node->getElementsByTagName('td')[0]->textContent),
                    'value' => trim($node->getElementsByTagName('td')[1]->textContent),
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
                    'fullsize' => 'https://i.otto.ru/i/otto/' . trim($node->getAttribute('rel')),
                    'thumb'    => '',
                ];
            }
        // }
        return $data;
    }

    public function pageQuery(int $page, string $url)
    {
        if (strpos($url, '?') !== false) {
            $pageQuery = '&p=';
        } else {
            $pageQuery = '?p=';
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
            // if ($category->source_category_alias) {
            //     $url = $domain . self::ACTION_SEARCH . '&' . self::QUERY_CATEGORY . $category->source_category_alias . '&' . self::QUERY_KEYWORD . $keyword;
            // } else {
                $url = $this->processUrl($category->source_url) . '?' . self::QUERY_KEYWORD . $keyword;
                // $url = $domain . $category->source_url . '?' . self::QUERY_KEYWORD . $keyword;
            // }
        }

        if ($categorySourceId != null && $keyword == '') {
            // if ($category->source_category_alias) {
            //     $url = $domain . self::ACTION_SEARCH . '&' . self::QUERY_CATEGORY . $category->source_category_alias;
            // } else {
                $url = $this->processUrl($category->source_url);
                // $url = $domain . $category->source_url;
            // }
        }

        if ($categorySourceId == null && $keyword != '') {
            $url = $domain . self::ACTION_SEARCH . '&' . self::QUERY_KEYWORD . $keyword;
        }

        return $url;
    }

}
