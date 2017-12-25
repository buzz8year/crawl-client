<?php

namespace backend\models\parser\source;

use backend\models\CategorySource;
use backend\models\History;
use backend\models\parser\Parser;
use backend\models\parser\ParserSourceInterface;
use backend\models\Source;

class LetualParser extends Parser implements ParserSourceInterface
{

    const ACTION_SEARCH  = 'atgsearch/atgSearchResults.jsp?';
    const QUERY_CATEGORY = 'N=';
    const QUERY_KEYWORD  = 'Ntt=';

    // const XPATH_WARNING = '//h1[contains(@class, \'emptySearchResult\')]'; // At Catalog/Search Page
    const XPATH_WARNING = ''; // At Catalog/Search Page
    const XPATH_CATALOG = '//div[contains(@class, \'categoryContainer\')]//div[contains(@class, \'productItem\')]'; // At Catalog/Search Page
    // const XPATH_CATALOG = '//div[contains(@class, \'categoryContainer\')]//div[contains(@class, \'productItemDescription\')]'; // At Catalog/Search Page

    // const XPATH_SUPER = '//notelement'; // At Product Page. JS Script with JSON Whole Data Object
    const XPATH_SUPER = ''; // At Product Page. JS Script with JSON Whole Data Object
    const XPATH_DESCRIPTION = '//div[contains(@itemprop, \'description\')]'; // At Product Page
    const XPATH_IMAGE = '//img[contains(@itemprop, \'image\')]'; // At Product Page. Full size.
    const XPATH_ATTRIBUTE   = '//notelement'; // At Product Page

    const LEVEL_ONE_CATEGORY_NODE    = '//ul[@id=\'nav\']/li'; // At HomePage sidebar
    // const LEVEL_TWO_CATEGORY   = 5; // At Level One Category Page leftmenu
    // const LEVEL_THREE_CATEGORY = 7; // At Level One Category Page leftmenu
    // const ALIAS_CATEGORY_NODE  = '//input[contains(@id, \'currentCategoryDimensionId\')]';

    const CURL_FOLLOW = 0; // CURLOPT_FOLLOWLOCATION

    // const MAX_QUANTITY = 990; // Real range is from 990 to ~1140 (+/-)

    public function parseCategories()
    {
        $data = [];

        if ($response = $this->curlSession(self::$model->domain . '/company/sitemap.jsp')) {
            // print_r($response);
            if (($levelsOne = $this->getNodes($response, self::LEVEL_ONE_CATEGORY_NODE)) && $levelsOne->length) {
            // print_r($levelsOne);
                foreach ($levelsOne as $keyOne => $levelOne) {
                    if (($linksOne = $levelOne->getElementsByTagName('a')) && $linksOne->length) {
                        $data[$keyOne] = [
                            'csid'       => '',
                            'dump'       => '',
                            'alias'      => '',
                            'href'       => $this->processUrl($linksOne[0]->getAttribute('href')),
                            'title'      => trim($linksOne[0]->textContent),
                            'nest_level' => 0,
                        ];
                    }
                    if (($lisTwo = $levelOne->getElementsByTagName('li')) && $lisTwo->length) {
                        foreach ($lisTwo as $keyTwo => $liTwo) {
                            if ($liTwo->parentNode->parentNode === $levelOne && ($linksTwo = $liTwo->getElementsByTagName('a')) && $linksTwo->length) {
                                $data[$keyOne]['children'][$keyTwo] = [
                                    'csid'       => '',
                                    'dump'       => '',
                                    'alias'      => '',
                                    'href'       => $this->processUrl($linksTwo[0]->getAttribute('href')),
                                    'title'      => trim($linksTwo[0]->textContent),
                                    'nest_level' => 1,
                                ];
                                if (($lisThree = $liTwo->getElementsByTagName('li')) && $lisThree->length) {
                                    foreach ($lisThree as $liThree) {
                                        if (($linksThree = $liThree->getElementsByTagName('a')) && $linksThree->length) {
                                            $data[$keyOne]['children'][$keyTwo]['children'][] = [
                                                'csid'       => '',
                                                'dump'       => '',
                                                'alias'      => '',
                                                'href'       => $this->processUrl($linksThree[0]->getAttribute('href')),
                                                'title'      => trim($linksThree[0]->textContent),
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

        return $data;
    }



    // public function parseCategories()
    // {
    //     // LEVEL ONE, TWO & THREE Categories
    //     $response      = $this->curlSession(self::$model->domain);
    //     $levelOneNodes = $this->getNodes($response, self::LEVEL_ONE_CATEGORY_NODE);

    //     $data = [];
        
    //     $levelOneCurrent = -1;
    //     $levelTwoCurrent = -1;

    //     foreach ($levelOneNodes as $node) {
    //         $levelcat = 0;
    //         $nodeTmp = $node;
    //         while ($parentNode = $nodeTmp->parentNode) {
    //             if ( trim($parentNode->getAttribute('class')) == 'headerMenu' )
    //                 break;
    //             else
    //                 $levelcat++;
    //             $nodeTmp = $parentNode;
    //         }
    //         $hrefMenu = trim ( $node->getAttribute('href') );
    //         $titleMenu = trim ( $node->textContent );
    //         $aliasMenu = null;
            
    //         $response   = $this->curlSession(self::$model->domain . $hrefMenu);
    //         $AliasNodes = $this->getNodes($response, self::ALIAS_CATEGORY_NODE);
    //         foreach ($AliasNodes as $AliasNode) {
    //             $aliasMenu = $AliasNode->getAttribute('value');
    //         }
            
    //         switch ( $levelcat ) {
    //             case self::LEVEL_TWO_CATEGORY :
    //                 if ( $levelOneCurrent < 0 )
    //                     break;
    //                 $levelTwoCurrent++;
                    
    //                 $data[$levelOneCurrent]['children'][$levelTwoCurrent] = [
    //                     'dump'  => '' ,
    //                     'href'  => self::$model->domain . $hrefMenu ,
    //                     'alias' => $aliasMenu ,
    //                     'csid'  => '' ,
    //                     'title' => $titleMenu != '' ? ( $data[$levelOneCurrent]['title'] . " > " . $titleMenu ) : '--' ,
    //                 ];
    //             break;
                
    //             case self::LEVEL_THREE_CATEGORY :
    //                 if ( $levelOneCurrent < 0 OR $levelTwoCurrent < 0 )
    //                     break;
                    
    //                 $data[$levelOneCurrent]['children'][$levelTwoCurrent]['children'][] = [
    //                     'dump'  => '' ,
    //                     'href'  => self::$model->domain . $hrefMenu ,
    //                     'alias' => $aliasMenu ,
    //                     'csid'  => '' ,
    //                     'title' => $titleMenu != '' ? ( $data[$levelOneCurrent]['children'][$levelTwoCurrent]['title'] . " > " . $titleMenu ) : '--' ,
    //                 ];
    //             break;
                
    //             default :
    //                 $levelOneCurrent++;
    //                 $levelTwoCurrent = -1;

    //                 if ($levelOneCurrent == 3) {
    //                     break 2;
    //                 }
    //                 // Do not forget to REMOVE
                    
    //                 $data[$levelOneCurrent] = [
    //                     'dump'  => '' ,
    //                     'href'  => self::$model->domain . $hrefMenu ,
    //                     'alias' => $aliasMenu ,
    //                     'csid'  => '' ,
    //                     'title' => $titleMenu != '' ? $titleMenu : '--' ,
    //                 ];
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
        $extend = ' and .//p[@class=\'wrItemOldPrice\']';
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
            $link = $node->getElementsByTagName('a')[0]->getAttribute('href');
            // $price = preg_replace("/[^0-9]/", '', $node->parentNode->parentNode->getElementsByTagName('b')[0]->textContent);
            foreach ($node->getElementsByTagName('p') as $p) {
                if ($p->getAttribute('class') && $p->getAttribute('class') == 'wrItemMinPrice') {
                    $price = preg_replace('/[^0-9]/', '', $p->textContent);
                }
            }
            $name  = trim($node->getElementsByTagName('a')[0]->textContent);
            // $href  = $link;

            // if ($href) {
            // if ($link && $price) {
            if ($link && $price) {
                $exp = explode(';', $link);
                $data[] = [
                    // 'id'         => $id,
                    'name'       => $name,
                    'price'      => $price,
                    'href'       => self::$model->domain . '/' . $exp[0],
                    // 'href'       => self::$model->domain . $href,
                    // 'attributes' => [],
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
     * Getting descriptions data from the object produced by getSuperData()
     * @return array
     */
    public function getDescriptionData($object)
    {
        $data = [];
        foreach ($object as $node) {
            $data[] = [
                'title' => '',
                'text'  => trim($node->textContent),
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
                'fullsize' => self::$model->domain . trim($node->getAttribute('src')),
                'thumb'    => '',
            ];
        }
        return $data;
    }

    public function pageQuery(int $page, string $url)
    {
        $pageQuery = '&Nrpp=24&No=';
        return $page > 0 ? ( $pageQuery . ( $page * 24 ) ) : '';
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
                $url = $domain . self::ACTION_SEARCH . self::QUERY_CATEGORY . $category->source_category_alias . '&' . self::QUERY_KEYWORD . urlencode ( $keyword );
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
            $url = $domain . self::ACTION_SEARCH . self::QUERY_KEYWORD . urlencode ( $keyword );
        }

        return $url;
    }

}
