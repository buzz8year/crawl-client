<?php

namespace crawler\parser\impl;

use crawler\models\CategorySource;
use crawler\models\History;
use crawler\parser\Parser;
use crawler\parser\ParserSourceInterface;
use crawler\models\source\Source;


class OnlinetradeParser extends Parser implements ParserSourceInterface
{

    const ACTION_SEARCH  = '/sitesearch.html?';
    const QUERY_CATEGORY = 'cat_id=';
    const QUERY_KEYWORD  = 'query=';

    const XPATH_WARNING = '//div[contains(@class, \'k_centered\')]/div/div[contains(@class, \'whitebox\')]'; // At Catalog/Search Page

    // NOTE: Селектор заточен только под страницу поиска, на странице каталога с ним ничего не найдется
    // const XPATH_CATALOG = '//div[contains(@itemprop, \'search__findedItem\')]'; // At Catalog/Search Page

    const XPATH_CATALOG = '//div[contains(@itemprop, \'search__findedItem\') or @class=\'catalog__displayedItem catalog__displayedItemFotomode\']'; // At Catalog/Search Page


    const XPATH_SUPER = '//notelement'; // At Product Page. JS Script with JSON Whole Data Object
    const XPATH_ATTRIBUTE   = '//*[contains(@id, \'tabs_description\')]//*[contains(@class, \'dottedList__backLine\')]/parent::*'; // At Product Page
    const XPATH_DESCRIPTION = '//*[contains(@class, \'descriptionText_cover\')]'; // At Product Page
    const XPATH_IMAGE = '//img[contains(@class, \'popupCatalogItem_bigImage\')]'; // At Product Page. Full size.

    const LEVEL_ONE_CATEGORY_NODE    = '//*[contains(@class, \'mainCategoryMenu\')]//li'; // At HomePage navmenu
    const LEVEL_TWO_CATEGORY_NODE    = '//*[contains(@class, \'eLeftMainMenu_ElementsBlock\')]'; // At Level One Category Page leftmenu
    const LEVEL_THREE_CATEGORY_NODE  = '//a'; // At Level One Category Page leftmenu

    const CURL_FOLLOW = 0; // CURLOPT_FOLLOWLOCATION

    const MAX_QUANTITY = 990; // Real range is from 990 to ~1140 (+/-)


    public function idCategory($url)
    {
        $tmp = explode ( '-c' , $url );
        $string = end ( $tmp );
        return intval ( substr ( $string , 0 , strlen ( $string ) - 1 ) );
    }
    
    public function parseCategories()
    {
        $response      = $this->curlSession(self::$model->domain);
        $levelOneNodes = $this->getNodes($response, self::LEVEL_ONE_CATEGORY_NODE);

        $data = [];
        
        $currentCategoryOne = -1;
        $childrenThree = array ( );
        // LEVEL ONE & TWO Categories
        foreach ($levelOneNodes as $tempKey => $nodeOne) {
            switch ( $nodeOne->getAttribute('class') ) {
                case 'mCM__item' :
                    // NOTE: Break'и нужно убирать
                    // if ( $currentCategoryOne == 3 )
                    //     break 2;
                    $currentCategoryOne++;
                    $currentCategoryTwo = 0;
                    $href = $nodeOne->getElementsByTagName('a')[0]->getAttribute('href');
                    $alias = $this->idCategory($href);
                    $data[$currentCategoryOne] = [
                        'dump'  => '',
                        'href'  => self::$model->domain . $href,
                        'alias' => $alias,
                        'csid'  => '',
                        'title' => trim($nodeOne->getElementsByTagName('a')[0]->textContent),
                        // NOTE: Пишите 'nest_level', пжлста. В первой версии их не было, но теперь есть.
                        'nest_level' => 0
                    ];
                    
                    // LEVEL THREE Categories
                    $childrenThree = array ( );
                    $response2      = $this->curlSession(self::$model->domain . "/ajax.php?handler=menucatalogue&catid=" . $alias);
                    $response_ar = json_decode ( $response2 , true );
                    if ( count ( $response_ar ) > 0 ) {
                        foreach ( $response_ar AS $key => $value ) {
                            if ( $value != '' ) {
                                $levelThreeNodes = $this->getNodes($value, self::LEVEL_THREE_CATEGORY_NODE);
                                foreach ($levelThreeNodes as $tempKeyThree => $nodeThree) {
                                    $href = $nodeThree->getAttribute('href');
                                    $alias = $this->idCategory($href);
                                    $childrenThree[$key][] = [
                                        'dump'  => '',
                                        'href'  => self::$model->domain . $href,
                                        'alias' => $alias,
                                        'csid'  => '',
                                        'title' => trim($nodeThree->textContent),
                                        // NOTE: Пишите 'nest_level', пжлста
                                        'nest_level' => 2
                                    ];
                                }
                            }
                        }
                    }
                break;
                case 'sCM__item' :
                    $href = $nodeOne->getElementsByTagName('a')[0]->getAttribute('href');
                    $alias = $this->idCategory($href);
                    $data[$currentCategoryOne]['children'][$currentCategoryTwo] = [
                        'dump'  => '',
                        'href'  => self::$model->domain . $href,
                        'alias' => $alias,
                        'csid'  => '',
                        'title' => trim($nodeOne->getElementsByTagName('a')[0]->textContent),
                        // NOTE: Пишите 'nest_level', пжлста
                        'nest_level' => 1
                    ];
                    if ( isset ( $childrenThree[$alias] ) )
                        $data[$currentCategoryOne]['children'][$currentCategoryTwo]['children'] = $childrenThree[$alias];
                    $currentCategoryTwo++;
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

    // NOTE: Пжлста, учитывайте, что в интерфейсе теперь данный метод теперь без строгого указания типа
    // public function getProducts(\DOMNodeList $nodes)
    public function getProducts($nodes)
    {
        print_r('Найдено эл-ов по указ. селекторам: ' . $nodes->length);
        $data = [];

        foreach ($nodes as $node) {
            $id    = false;
            $price = false;
            $name  = false;
            $href  = false;
            
            $divs = $node->getElementsByTagName('div');
            foreach ($divs as $div) {
                if ( $div->getAttribute('class') == 'search__findedItem__columnFoto' ) {
                    $id = $div->getElementsByTagName('a')[1]->getAttribute('data-itemid');
                    $name = $div->getElementsByTagName('a')[0]->getElementsByTagName('img')[0]->getAttribute('alt');
                    $href  = $node->getElementsByTagName('a')[0]->getAttribute('href');
                }
                if ( $div->getAttribute('class') == 'catalog__displayedItem__actualPrice' ) {
                    $price = intval ( str_replace ( ' ' , '' , $div->textContent ) );
                }
            }

            if ($id&&$name&&$href&&$price) {
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
        return [];
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
                    'text'  => $node->textContent,
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
        foreach ($object as $node) {
            $data[] = [
                'title' => trim($node->getElementsByTagName('span')[0]->textContent),
                'value' => trim($node->getElementsByTagName('span')[1]->textContent),
            ];
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
                    'fullsize' => $node->getElementsByTagName('img')[0]->getAttribute('src'),
                    'thumb'    => '',
                ];
            }
        }
        return $data;
    }

    public function pageQuery(int $page, string $url)
    {
        $pageQuery = '?page=';

        return $page > 1 ? $pageQuery .  ( $page - 1 ) : '';
    }

    public function buildUrl(string $regionSourceId = '', string $categorySourceId = '', string $keyword = '')
    {
        $domain   = self::$model->domain;
        $category = CategorySource::findOne($categorySourceId);

        $keyword = str_replace(' ', '+', $keyword);

        $url = '';

        if ($categorySourceId != null && $keyword != '') {
            if ($category->source_category_alias) {
                $url = $domain . self::ACTION_SEARCH . '&' . self::QUERY_CATEGORY . $category->source_category_alias . '&' . self::QUERY_KEYWORD . $keyword;
            } else {
                $url = $domain . $category->source_url . '?' . self::QUERY_KEYWORD . $keyword;
            }
        }

        if ($categorySourceId != null && $keyword == '') {

            // NOTE: Не было указано ключевого слова - соотв., делать запрос на поиск - неправильно
            // if ($category->source_category_alias) {
            //     $url = $domain . self::ACTION_SEARCH . '&' . self::QUERY_CATEGORY . $category->source_category_alias;
            // } else {
                // $url = $domain . $category->source_url;
            // }

            $url = $category->source_url;
        }

        if ($categorySourceId == null && $keyword != '') {
            $url = $domain . self::ACTION_SEARCH . '&' . self::QUERY_KEYWORD . $keyword;
        }

        return $url;
    }

}