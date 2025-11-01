<?php

namespace crawler\parser\impl;

use crawler\models\CategorySource;
use crawler\models\History;
use crawler\parser\Parser;
use crawler\parser\ParserSourceInterface;
use crawler\models\source\Source;
use Yii;

class DecatlonParser extends Parser implements ParserSourceInterface
{

    const ACTION_SEARCH  = 'kupit/';
    const QUERY_CATEGORY = '';
    const QUERY_KEYWORD  = '/';

    const XPATH_WARNING = ''; // At Catalog/Search Page
    const XPATH_CATALOG = '//li[contains(@class, \'new-product-thumbnail\')]'; // At Catalog/Search Page

    const XPATH_SUPER = '//notelement'; // At Product Page. JS Script with JSON Whole Data Object
    const XPATH_DESCRIPTION = './/div[@id=\'floorGroupDetailedInformation\']'; // At Product Page
    const XPATH_IMAGE = '//img[@id=\'productMainPicture\']'; // At Product Page. Full size.
    const XPATH_ATTRIBUTE   = '//notelement'; // At Product Page

    const LEVEL_ONE_CATEGORY_NODE    = '//*[@id=\'main-menu-vertical\']'; // At HomePage sidebar
    const LEVEL_TWO_CATEGORY   = 5; // At Level One Category Page leftmenu
    const LEVEL_THREE_CATEGORY_NODE = '//ul[@class=\'breadcrumb-sub-nav\']'; // At Level One Category Page leftmenu
    const ALIAS_CATEGORY_NODE  = '//notelement';

    const CURL_FOLLOW = 0; // CURLOPT_FOLLOWLOCATION

    const MAX_QUANTITY = 990; // Real range is from 990 to ~1140 (+/-)


    public function parseCategories()
    {
        // LEVEL ONE, TWO & THREE Categories
        $response      = $this->curlSession(self::$model->domain);
        $nodes = $this->getNodes($response, self::LEVEL_ONE_CATEGORY_NODE);
        $child = $nodes[0]->childNodes;
        $levelOneNodes = $child[0]->getElementsByTagName('li');
        $levelTwoNodes = $child[1]->childNodes;
        //$levelTwoNodes = $child[1]->getElementsByTagName('li');
        //$levelTwoNodes = $child[1];
        $data = [];
        foreach ($levelOneNodes as $keyNodeLevelOne => $nodeLevelOne)
        {
            $titleMenu = $nodeLevelOne->getElementsByTagName('a')[0]->textContent;
            $data[$keyNodeLevelOne] = [
                'dump'  => '' ,
                'href'  => self::$model->domain . $nodeLevelOne->getElementsByTagName('a')[0]->getAttribute('href') ,
                'alias' => '' ,
                'csid'  => $nodeLevelOne->getAttribute('data-category-id') ,
                'title' => $titleMenu != '' ? $titleMenu : '--' ,
            ];

            foreach ($levelTwoNodes as $nodeLevelTwo)
            {
                if ($nodeLevelTwo->localName)
                {
                    $id_parent = $nodeLevelTwo->getAttribute('data-category-id');
                    if ($data[$keyNodeLevelOne]['csid'] == $id_parent)
                    {
                        $nodeLevelTwoCats = $nodeLevelTwo->getElementsByTagName('ul')[0];
                        $nodeLevelTwoCats = $nodeLevelTwoCats->getElementsByTagName('li');
                        foreach ($nodeLevelTwoCats as $keyNodeLevelTwoCat => $nodeLevelTwoCat)
                        {
                            $idCategoryTwo = $nodeLevelTwoCat->getAttribute('secondarycategoryid');

                            if ((int)$idCategoryTwo) 
                            {
                                $titleMenu = $nodeLevelTwoCat->getElementsByTagName('a')[0]->textContent;
                                $data[$keyNodeLevelOne]['children'][$keyNodeLevelTwoCat] = [
                                    'dump'  => '' ,
                                    'href'  => self::$model->domain . $nodeLevelTwoCat->getElementsByTagName('a')[0]->getAttribute('href') ,
                                    'alias' => '' ,
                                    'csid'  => $idCategoryTwo ,
                                    'title' => $titleMenu != '' ? $titleMenu : '--' ,
                                ];
                                $response   = $this->curlSession($data[$keyNodeLevelOne]['children'][$keyNodeLevelTwoCat]['href']);
                                $nodesThree = $this->getNodes($response, self::LEVEL_THREE_CATEGORY_NODE);
                                if ($nodesThree->length != 0) 
                                {
                                    $nodesThreeLevel = $nodesThree[0]->getElementsByTagName('li');
                                    foreach ($nodesThreeLevel as $keyNodeThreeLevel => $nodeThreeLevel) 
                                    {
                                        $idCategoryThree = explode('|', $nodeThreeLevel->getElementsByTagName('a')[0]->getAttribute('data-track-breadcrumb'))[1];
                                        $titleMenu = $nodeThreeLevel->getElementsByTagName('a')[0]->textContent;
                                        $data[$keyNodeLevelOne]['children'][$keyNodeLevelTwoCat]['children'][$keyNodeThreeLevel] = [
                                            'dump'  => '' ,
                                            'href'  => self::$model->domain . $nodeThreeLevel->getElementsByTagName('a')[0]->getAttribute('href'),
                                            'alias' => '' ,
                                            'csid'  => $idCategoryThree ,
                                            'title' => $titleMenu != '' ? $titleMenu : '--' ,
                                        ];
                                    }
                                }
                            }
                        }

                    }

                }

            }
            if ($keyNodeLevelOne == 4)
                break;
        }
        return $data;
    }

    /**
     * @return array
     */
    public function getWarningData(\DOMNodeList $nodes)
    {
        foreach ($nodes as $node)
            $data[] = $node->textContent;
        return $data;
    }

    /**
     * Extracting data from the product item's element of a category/search page
     * @return array
     */

    public function getProducts($nodes)
    {
        $data = [];
        foreach ($nodes as $node) {
            $link_image = $node->getAttribute('data-product-imgurl');
            $link = $node->getElementsByTagName('a')[0]->getAttribute('href');
            $price = $node->getAttribute('data-product-price');
            $name = $node->getAttribute('data-product-name');
            $id = $node->getAttribute('data-product-id');
            $href  = $link;

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
        Yii::error($data, 'debug-getProducts');
        return $data;
    }

    /**
     * Extracting an object (of all the data needed) from the <script/> element
     */
    public function getSuperData(\DOMNodeList $nodes)
    {
        return array();
    }

    /**
     * Getting descriptions data from the object produced by getSuperData()
     * @return array
     */
    public function getDescriptionData($object)
    {
        $data = [];
        if (isset($object->Description)) 
        {
            foreach ($object->Description->Blocks as $descScope) 
            {
                $data[] = [
                    'title' => $descScope->Title,
                    'text'  => $descScope->Text,
                ];
            }
        } 
        else {
            foreach ($object as $node) 
            {
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
                    'fullsize' => self::$model->domain . trim($node->getAttribute('src')),
                    'thumb'    => '',
                ];
            }
        }
        return $data;
    }

    public function pageQuery(int $page, string $url)
    {
        $pageQuery = '#page=';
        return $page > 0 ? ( $pageQuery . $page ) : '';
    }

    public function buildUrl(string $regionSourceId = '', string $categorySourceId = '', string $keyword = '')
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
