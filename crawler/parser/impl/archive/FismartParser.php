<?php

namespace crawler\parser\impl;

use crawler\models\CategorySource;
use crawler\models\History;
use crawler\parser\Parser;
use crawler\parser\ParserSourceInterface;
use crawler\models\source\Source;


class FismartParser extends Parser implements ParserSourceInterface
{

    const ACTION_SEARCH  = '/search/?SHOWALL_2=1';
    const QUERY_CATEGORY = '';
    const QUERY_KEYWORD  = 'q=';

    const XPATH_WARNING = '//div[contains(@class, \'content\')]/div/div[contains(@class, \'container\')]/div[position() = 1 and contains(@class, \'ajax-search-contain\')]'; // At Catalog/Search Page
    const XPATH_CATALOG = '//div[contains(@class, \'cat_item\')]'; // At Catalog/Search Page

    const XPATH_SUPER = '//notelement'; // At Product Page. JS Script with JSON Whole Data Object
    const XPATH_ATTRIBUTE   = '//div[contains(@class, \'pars_wrap\')]'; // At Product Page
    const XPATH_DESCRIPTION = '//li[div[contains(@class, \'info_wp-head\')][contains(text(), \'Описание\')]]'; // At Product Page
    const XPATH_IMAGE = '//div[contains(@class, \'detpage_im\')]//a[@data-id]'; // At Product Page. Full size.

    const LEVEL_ONE_CATEGORY_NODE    = '//*[contains(@class, \'head_menu\')]//li'; // At HomePage navmenu

    const CURL_FOLLOW = 0; // CURLOPT_FOLLOWLOCATION

    const MAX_QUANTITY = 990; // Real range is from 990 to ~1140 (+/-)


    public function aliasCategory($url)
    {
        $tmp = explode ( '/' , $url );
        $count = count ( $tmp );
        return $tmp[$count-2];
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
                case 'has_inn' :
                    // if ( $currentCategoryOne == 3 )
                    //     break 2;
                    $currentCategoryOne++;
                    $currentCategoryTwo = 0;
                    $href = $nodeOne->getElementsByTagName('a')[0]->getAttribute('href');
                    $alias = $this->aliasCategory($href);
                    $data[$currentCategoryOne] = [
                        'dump'  => '',
                        'href'  => self::$model->domain . $href,
                        'alias' => $alias,
                        'csid'  => '',
                        'title' => trim($nodeOne->getElementsByTagName('a')[0]->textContent),
                        'nest_level' => 0,
                    ];
                break;
                case '' :
                    $href = $nodeOne->getElementsByTagName('a')[0]->getAttribute('href');
                    $alias = $this->aliasCategory($href);
                    $data[$currentCategoryOne]['children'][$currentCategoryTwo] = [
                        'dump'  => '',
                        'href'  => self::$model->domain . $href,
                        'alias' => $alias,
                        'csid'  => '',
                        'title' => trim($nodeOne->getElementsByTagName('a')[0]->textContent),
                        'nest_level' => 1,
                    ];
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
        foreach ($nodes as $node)
            $data[] = 'Найдено: 0 результатов';

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
        $category = CategorySource::find()->where(['source_id' => $_GET['id'], 'id' => $_GET['cat']])->one();
        // $category = CategorySource::find()->where(['source_id' => $_GET['id'],'category_id'=>$_GET['cat']])->one();

        foreach ($nodes as $node) {
            $href  = $node->getElementsByTagName('a')[1]->getAttribute('href');
            $tmp = explode ( '/' , $href );
            if ( $tmp[2] != $category->source_category_alias ) {
                $id    = $node->getAttribute('id');
                $price = intval ( str_replace ( ' ' , '' , $node->getElementsByTagName('p')[0]->textContent ) );
                $name  = $node->getElementsByTagName('a')[1]->textContent;

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
        }
        return $data;
    }

    /**
     * Extracting an object (of all the data needed) from the <script/> element
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
        if (isset($object->Description)) 
        {
            foreach ($object->Description->Blocks as $descScope) {
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
                    'text'  => $node->getElementsByTagName('div')[1]->textContent,
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
        foreach ($object as $node) 
        {
            $data[] = [
                'title' => trim($node->getElementsByTagName('div')[0]->textContent),
                'value' => trim($node->getElementsByTagName('div')[1]->textContent),
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
        if (isset($object->Gallery)) 
        {
            foreach ($object->Gallery->Groups[0]->Elements as $imageScope) 
            {
                $data[] = [
                    'fullsize' => $imageScope->Original,
                    'thumb'    => $imageScope->Preview,
                ];
            }
        } 
        else {
            foreach ($object as $node) 
            {
                $data[] = [
                    'fullsize' => $node->getAttribute('href'),
                    'thumb'    => $node->getElementsByTagName('img')[0]->getAttribute('src'),
                ];
            }
        }
        return $data;
    }

    public function pageQuery(int $page, string $url)
    {
        $pageQuery = '&q=!&SHOWALL_2=0';

        return $page > 1 ? $pageQuery : '';
    }

    public function buildUrl(string $regionSourceId = '', string $categorySourceId = '', string $keyword = '')
    {
        $domain   = self::$model->domain;
        $category = CategorySource::findOne($categorySourceId);

        $keyword = str_replace(' ', '+', $keyword);

        $url = '';

        if ($categorySourceId != null && $keyword != '') 
        {
            if ($category->source_category_alias)
                $url = $domain . self::ACTION_SEARCH . '&' . self::QUERY_KEYWORD . $keyword;
            else $url = $domain . $category->source_url . '?' . self::QUERY_KEYWORD . $keyword;
        }

        if ($categorySourceId != null && $keyword == '') {
            // if ($category->source_category_alias) {
            //     $url = $domain . self::ACTION_SEARCH . '&' . self::QUERY_KEYWORD . $keyword;
            // } else {
                $url = $category->source_url;
            // }
        }

        if ($categorySourceId == null && $keyword != '')
            $url = $domain . self::ACTION_SEARCH . '&' . self::QUERY_KEYWORD . $keyword;

        return $url;
    }

}