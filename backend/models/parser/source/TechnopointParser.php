<?php

namespace backend\models\parser\source;

use backend\models\CategorySource;
use backend\models\History;
use backend\models\parser\Parser;
use backend\models\parser\ParserSourceInterface;
use backend\models\Source;

class TechpointParser extends Parser implements ParserSourceInterface
{

    const ACTION_SEARCH  = 'search/?';
    const QUERY_CATEGORY = '';
    const QUERY_KEYWORD  = 'q=';

    const XPATH_WARNING = '//*[contains(@id, \'empty-search-results\')]'; // At Catalog/Search Page
    const XPATH_CATALOG = '//*[contains(@class, \'catalog-products\')]//div[contains(@data-id, \'product\')]'; // At Catalog/Search Page
    
    const XPATH_BREADCRUMBS = '//ol[contains(@class, \'breadcrumb\')]/li/a'; // At Catalog/Search Page

    const XPATH_SUPER = '//notelement'; // At Product Page. JS Script with JSON Whole Data Object
    const XPATH_DESCRIPTION = '//div[contains(@itemprop, \'description\')]/p'; // At Product Page
    const XPATH_IMAGE = '//div[contains(@class, \'thumb\')]/a'; // At Product Page. Full size.
    const XPATH_ATTRIBUTE   = '//tr[contains(@class, \'extended-characteristic\')]'; // At Product Page

    const LEVEL_ONE_CATEGORY_NODE    = '//body/li'; // At HomePage sidebar
    const LEVEL_TWO_CATEGORY_NODE   = '//ul[contains(@class, \'level-1\')]/li'; // At Level One Category Page leftmenu
    const LEVEL_THREE_CATEGORY_NODE = '//ul[contains(@class, \'level-2\')]/li'; // At Level One Category Page leftmenu

    const CURL_FOLLOW = 0; // CURLOPT_FOLLOWLOCATION

    const MAX_QUANTITY = 990; // Real range is from 990 to ~1140 (+/-)


    public function parseCategories()
    {
        // LEVEL ONE Categories
        $response      = $this->curlSession(self::$model->domain);
        preg_match_all("'https:\/\/as.technopoint.ru\/assets\/menu\/desktop-(.*).js'i", $response , $matches );
        
        $response      = $this->curlSession($matches[0][0]);
        $response      = str_replace ( array ( 'MenuWidget.setReplacements(\'desktop-' . $matches[1][0] . '.js\', {"desktop":"' , '"});' , '\"' ) , array ( '' , '' , '"' ) , $response );
        
        $levelOneNodes = $this->getNodes($response, self::LEVEL_ONE_CATEGORY_NODE);

        $data = [];

        foreach ($levelOneNodes as $node) {
            $hrefMenu = trim ( $node->getElementsByTagName('a')[0]->getAttribute('href') );
            $titleMenu = iconv ( 'UTF-8' , 'ISO-8859-1' , trim ( $node->getElementsByTagName('a')[0]->textContent ) );
            
            $levelOne = [
                'dump'  => '' ,
                'href'  => self::$model->domain . $hrefMenu ,
                'alias' => md5 ( $hrefMenu ) ,
                'csid'  => '' ,
                'title' => $titleMenu != '' ? $titleMenu : '--' ,
            ];
            
            // LEVEL TWO Categories
            $levelTwoCurrent = array ( );
            $levelTwoNodes = $this->getNodes(iconv ( 'UTF-8' , 'ISO-8859-1' , $node->ownerDocument->saveHTML($node)), self::LEVEL_TWO_CATEGORY_NODE);
            
            foreach ($levelTwoNodes as $nodeTwo) {
                $hrefMenu = trim ( $nodeTwo->getElementsByTagName('a')[0]->getAttribute('href') );
                $titleMenu = iconv ( 'UTF-8' , 'ISO-8859-1' , trim ( $nodeTwo->getElementsByTagName('a')[0]->textContent ) );
                
                $levelTwo = [
                    'dump'  => '' ,
                    'href'  => self::$model->domain . $hrefMenu ,
                    'alias' => md5 ( $hrefMenu ) ,
                    'csid'  => '' ,
                    'title' => $titleMenu != '' ? $titleMenu : '--' ,
                ];
                
                // LEVEL THREE Categories
                $levelThreeCurrent = array ( );
                $levelThreeNodes = $this->getNodes(iconv ( 'UTF-8' , 'ISO-8859-1' , $nodeTwo->ownerDocument->saveHTML($nodeTwo)), self::LEVEL_THREE_CATEGORY_NODE);
                
                foreach ($levelThreeNodes as $nodeThree) {
                    $hrefMenu = trim ( $nodeThree->getElementsByTagName('a')[0]->getAttribute('href') );
                    $titleMenu = iconv ( 'UTF-8' , 'ISO-8859-1' , trim ( $nodeThree->getElementsByTagName('a')[0]->textContent ) );
                    
                    $levelThree = [
                        'dump'  => '' ,
                        'href'  => self::$model->domain . $hrefMenu ,
                        'alias' => md5 ( $hrefMenu ) ,
                        'csid'  => '' ,
                        'title' => $titleMenu != '' ? $titleMenu : '--' ,
                    ];
                    
                    $levelThreeCurrent[] = $levelThree;
                }
                
                $levelTwo['children'] = $levelThreeCurrent;
                
                $levelTwoCurrent[] = $levelTwo;
            }
            
            $levelOne['children'] = $levelTwoCurrent;
            
            $data[] = $levelOne;
            
            if (count ( $data ) > 2) {
                break;
            }
        }
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

    public function getProducts(\DOMNodeList $nodes)
    {
        $data = [];
        $category = CategorySource::find()->where(['source_id' => $_GET['id'],'category_id'=>$_GET['cat']])->one();

        foreach ($nodes as $node) {
            $id    = $node->getAttribute('data-code');
            $link  = $node->getElementsByTagName('a')[0]->getAttribute('href');
            $priceAr = $node->getElementsByTagName('span');
            foreach ( $priceAr AS $value ) {
                if ( $value->getAttribute('data-product-param') == 'price' )
                    $price = $value->getAttribute('data-value');
            }
            $name  = trim($node->getElementsByTagName('h3')[0]->textContent);
            $href  = $link;
            if ($href) {
                $href  = self::$model->domain . $link;
                $response = $this->curlSession($href);
                if ( $response != '' ) {
                    $nodeProducts = $this->getNodes($response, self::XPATH_BREADCRUMBS);
                    
                    $addProd = false;
                    foreach ($nodeProducts as $nodeProduct) {
                        if ( md5 ( $nodeProduct->getAttribute('href') ) == $category->source_category_alias ) 
                            $addProd = true;
                    }
                    if ($addProd)
                        $data[] = [
                            'id'         => $id,
                            'name'       => $name,
                            'price'      => $price,
                            'href'       => $href,
                            'attributes' => [],
                        ];
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
        $script     = $nodes[0]->getElementsByTagName('script')[0]->nodeValue;
        $explodeOne = explode('({', $script)[1];
        $explodeTwo = explode('})', $explodeOne)[0];
        $data       = json_decode('{' . $explodeTwo . '}');

        return $data;
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
                    'text'  => trim($node->textContent),
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
        $data = [];
        if (isset($object->Capabilities)) {
            foreach ($object->Capabilities->Capabilities as $attrScope) {
                $data[] = [
                    'title' => $attrScope->Name,
                    'value' => is_string($attrScope->Value) ? $attrScope->Value : $attrScope->Value[0]->Text,
                ];
            }
        } else {
            foreach ($object as $node) {
                $data[] = [
                    'title' => trim($node->getElementsByTagName('td')[0]->textContent),
                    'value' => trim($node->getElementsByTagName('td')[1]->textContent),
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
                    'fullsize' => trim($node->getAttribute('href')),
                    'thumb'    => '',
                ];
            }
        }
        return $data;
    }

    public function pageQuery(int $page, string $url)
    {
        return $page > 0 ? '987654321' : '';
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
                $url = $domain . self::ACTION_SEARCH . self::QUERY_KEYWORD . $keyword;
            } else {
                $url = $category->source_url . '?' . self::QUERY_KEYWORD . $keyword;
            }
        }

        if ($categorySourceId != null && $keyword == '') {
            if ($category->source_category_alias) {
                $url = $domain . self::ACTION_SEARCH;
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
