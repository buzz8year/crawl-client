<?php

namespace backend\models\parser\source;

use backend\models\CategorySource;
use backend\models\History;
use backend\models\parser\Parser;
use backend\models\parser\ParserSourceInterface;
use backend\models\Source;

class RivegaucheParser extends Parser implements ParserSourceInterface
{

    const ACTION_SEARCH  = '/rest/v1/newRG/products?';
    const QUERY_CATEGORY = '%3A%3Acategory%3A';
    const QUERY_KEYWORD  = 'query=';

    const QUERY_PRODUCT_API = '/rest/v1/newRG/products?query=%3A%3Acode%3A';

    // const XPATH_WARNING = '//html'; // At Catalog/Search Page
    const XPATH_WARNING = ''; // At Catalog/Search Page
    const XPATH_CATALOG = '//html'; // At Catalog/Search Page
    
    const XPATH_BREADCRUMBS = '//*[contains(@class, \'breadCrumbsProduct\')]//a'; // At Catalog/Search Page

    const XPATH_SUPER = '//html'; // At Product Page. JS Script with JSON Whole Data Object
    const XPATH_DESCRIPTION = '//notelement'; // At Product Page
    const XPATH_IMAGE = '//notelement'; // At Product Page. Full size.
    const XPATH_ATTRIBUTE   = '//notelement'; // At Product Page

    const LEVEL_ONE_CATEGORY_NODE       = '//li[contains(@class, \'top-menu__item\')]'; // At HomePage sidebar
    const LEVEL_TWO_CATEGORY_NODE       = '//div[contains(@class, \'submenu__block\')]/*'; // At Level One Category Page leftmenu
    const LEVEL_THREE_CATEGORY_NODE     = '//a'; // At Level One Category Page leftmenu

    const CURL_FOLLOW = 1; // CURLOPT_FOLLOWLOCATION

    const MAX_QUANTITY = 990; // Real range is from 990 to ~1140 (+/-)


    public function parseCategories()
    {
        // LEVEL ONE Categories
        $response      = $this->curlSession(self::$model->domain . '/newstore');
        $levelOneNodes = $this->getNodes($response, self::LEVEL_ONE_CATEGORY_NODE);

        $data = [];
        
        $levelOneCurrent = -1;

        foreach ($levelOneNodes as $node) {
            $levelOneCurrent++;
            $levelTwoCurrent = -1;
            
            $hrefMenu = trim ( $node->getElementsByTagName('a')[0]->getAttribute('href') );
            $titleMenu = trim ( $node->getElementsByTagName('a')[0]->textContent );
            // $titleMenu = iconv ( 'UTF-8' , 'WINDOWS-1251' , iconv ( 'WINDOWS-1251' , 'UTF-8' , trim ( $node->getElementsByTagName('a')[0]->textContent ) ) );
            
            $linkExplode = explode ('/',$hrefMenu);
            $aliasMenu   = end($linkExplode);
            
            $data[$levelOneCurrent] = [
                'dump'  => '' ,
                'href'  => $this->processUrl($hrefMenu),
                'alias' => $aliasMenu ,
                'csid'  => '' ,
                'title' => $titleMenu != '' ? $titleMenu : '--' ,
                'nest_level' => 0,
                'children' => array()
            ];
            
            $response = $node->ownerDocument->saveHTML($node);
            
            $levelTwoNodes = $this->getNodes($response, self::LEVEL_TWO_CATEGORY_NODE);
            // LEVEL TWO & THREE Categories
            foreach ($levelTwoNodes as $nodeTwo) {
                if ( $nodeTwo->nodeName == 'div' ) {
                    $levelTwoCurrent++;
                    $hrefMenu = trim ( $nodeTwo->getElementsByTagName('a')[0]->getAttribute('href') );
                    $titleMenu = iconv ( 'UTF-8' , 'ISO-8859-1' , trim ( $nodeTwo->getElementsByTagName('a')[0]->textContent ) );
                    
                    $linkExplode = explode ('/',$hrefMenu);
                    $aliasMenu   = end($linkExplode);
                    
                    $data[$levelOneCurrent]['children'][$levelTwoCurrent] = [
                        'dump'  => '' ,
                        'href'  => self::$model->domain . $hrefMenu ,
                        'alias' => $aliasMenu ,
                        'csid'  => '' ,
                        'nest_level' => 1,
                        'title' => $titleMenu != '' ? $titleMenu : '--' ,
                        // 'title' => $titleMenu != '' ? ( $data[$levelOneCurrent]['title'] . " > " . $titleMenu ) : '--' ,
                    ];
                }
                elseif ( $nodeTwo->nodeName == 'ul' and $levelTwoCurrent > -1 ) {
                    $response = $nodeTwo->ownerDocument->saveHTML($nodeTwo);
                    $levelThreeNodes = $this->getNodes($response, self::LEVEL_THREE_CATEGORY_NODE);
                    
                    foreach ($levelThreeNodes as $nodeThree) {
                        $hrefMenu = trim ( $nodeThree->getAttribute('href') );
                        $titleMenu = iconv ( 'UTF-8' , 'ISO-8859-1' , iconv ( 'UTF-8' , 'ISO-8859-1' , trim ( $nodeThree->textContent ) ) );
                        
                        $linkExplode = explode ('/',$hrefMenu);
                        $aliasMenu   = end($linkExplode);
                        
                        $data[$levelOneCurrent]['children'][$levelTwoCurrent]['children'][] = [
                            'dump'  => '' ,
                            'href'  => self::$model->domain . $hrefMenu ,
                            'alias' => $aliasMenu ,
                            'csid'  => '' ,
                            'nest_level' => 2,
                            'title' => $titleMenu != '' ? $titleMenu : '--' ,
                            // 'title' => $titleMenu != '' ? ( $data[$levelOneCurrent]['children'][$levelTwoCurrent]['title'] . " > " . $titleMenu ) : '--' ,
                        ];
                    }
                }
            }

            // if ($levelOneCurrent == 2) {
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
        // $data = [];
        // foreach ($nodes as $node) {
        //     $json = json_decode ( $node->nodeValue , true );
        //     if ( count ( $json['products'] ) == 0 )
        //         $data[] = 'Ничего не найдено';
        // }

        // return $data;
    }




    // /**
    //  * @return
    //  */
    // public static function xpathSale(string $xpath)
    // {
    //     $extend = ' and (.//div[contains(@class, \'price-old\')])';
    //     $explode  = rtrim($xpath, ']');
    //     $xpath = $explode . $extend . ']';

    //     return $xpath;
    // }




    /**
     * Extracting data from the product item's element of a category/search page
     * @return array
     */
    // public function getProducts(\DOMNodeList $nodes)
    public function getProducts($nodes)
    {
        $data = [];
        // $category = CategorySource::find()->where(['source_id' => $_GET['id'],'category_id'=>$_GET['cat']])->one();
        foreach ($nodes as $node) {
            $json = json_decode ( $node->nodeValue , true );
            foreach ($json['products'] as $product) {

                $condition = true;

                if (self::$model->saleFlag === true) {
                    if (isset($product['discountPrice']) && $product['discountPrice']) {
                        $condition = true;
                    }
                    else {
                        $condition = false;
                    }
                }

                if ($condition) {
                    $href  = self::$model->domain . '/newstore/p/' . trim($product['code']);
                    $apiHref  = self::$model->domain . self::QUERY_PRODUCT_API . trim($product['code']);
                    // $linkExplode = explode ('/', $product['url']);
                    // $id    = end($linkExplode);
                    if ($product['price']) {
                        $price = $product[(self::$model->saleFlag ? 'discountPrice' : 'price')]['value'];
                    }
                    $name  = iconv ( 'UTF-8' , 'ISO-8859-1' , trim ( $product['name'] ) );
                    // $href  = $link;
                    if ($href && isset($price)) {
                        $data[] = [
                            // 'id'         => $id,
                            'name'       => $name,
                            'price'      => $price,
                            'href'       => $href,
                            'api_href'       => $apiHref,
                            // 'href'       => self::$model->domain . $href,
                            // 'attributes' => [],
                        ];
                    }
                }
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
        $data = array ( );
        foreach ($nodes as $node) {
            $json = json_decode ( $node->nodeValue , true );
            if ( count ( $json['products'] ) > 0 )
                $data = $json['products'][0];
        }
        return $data;
    }

    /**
     * Getting descriptions data from the object produced by getSuperData()
     * @return array
     */
    public function getDescriptionData($object)
    {
        $data = [];
        if (isset($object['description'])) {
            $data[] = [
                'title' => '',
                'text'  => trim(iconv ( 'UTF-8' , 'ISO-8859-1' , $object['description'])),
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
        if (isset($object['classifications'])) {
            foreach ($object['classifications'] as $classifications) {
                foreach ($classifications['features'] as $features) {
                    $data[] = [
                        'title' => trim(iconv ( 'UTF-8' , 'ISO-8859-1' , $features['name'])),
                        'value'  => trim(iconv ( 'UTF-8' , 'ISO-8859-1' , $features['featureValues'][0]['value'])),
                    ];
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
        if (isset($object['images'])) {
            $thumb = '';
            $original = '';
            foreach ($object['images'] as $image) {
                if ( $image['format'] == 'productPageDesktop' )
                    $original = self::$model->domain . trim ( $image['url'] );
                if ( $image['format'] == 'cartThumbnail' )
                    $thumb = self::$model->domain . trim ( $image['url'] );
            }
            $data[] = [
                'fullsize' => $original,
                'thumb'    => $thumb,
            ];
        }
        return $data;
    }

    public function pageQuery(int $page, string $url)
    {
        // $page = $page - 1;
        $pageQuery = '&currentPage=';
        return $page > 0 ? ( $pageQuery . $page ) : '';
    }

    public function urlBuild(string $regionSourceId = '', string $categorySourceId = '', string $keyword = '')
    {
        $model    = self::$model;
        $domain   = self::$model->domain;
        // $domain   = self::$model->domain . '/';
        $category = CategorySource::findOne($categorySourceId);

        $keyword = str_replace(' ', '+', $keyword);

        $url = '';

        if ($categorySourceId != null && $keyword != '') {
            if ($category->source_category_alias) {
                $url = $domain . self::ACTION_SEARCH . '&' . self::QUERY_KEYWORD . $keyword . '+' . self::QUERY_CATEGORY . $category->source_category_alias;
            } else {
                $url = $category->source_url . '?' . self::QUERY_KEYWORD . $keyword;
            }
        }

        if ($categorySourceId != null && $keyword == '') {
            if ($category->source_category_alias) {
                $url = $domain . self::ACTION_SEARCH . '&query=' . self::QUERY_CATEGORY . $category->source_category_alias;
            } else {
                $url = $category->source_url;
            }
        }

        if ($categorySourceId == null && $keyword != '') {
            $url = $domain . self::ACTION_SEARCH . '&' . self::QUERY_KEYWORD . $keyword;
        }

        return $url;
    }

}
