<?php

namespace backend\models\parser\source;

use backend\models\parser\ParserSourceInterface;
use backend\models\parser\Parser;
use backend\models\CategorySource;
use backend\models\Region;
use backend\models\Source;

class HolodilnikParser extends Parser implements ParserSourceInterface
{
    const ACTION_SEARCH  = 'search/?';
    const QUERY_CATEGORY = 'cat=';
    const QUERY_KEYWORD  = 'search=';

    const XPATH_WARNING = ''; // At Catalog/Search Page
    const XPATH_CATALOG = '//*[@class=\'prodbox\']'; // At Catalog/Search Page

    const XPATH_SUPER       = ''; // At Product Page. JS Script with JSON Whole Data Object
    const XPATH_ATTRIBUTE   = '//div[@class=\'det-content-block\']//tr'; // At Product Page
    const XPATH_DESCRIPTION = '//div[@itemprop=\'description\']'; // At Product Page
    const XPATH_IMAGE       = '//img[@class=\'img_big\']'; // At Product Page. Full size.

    const LEVEL_ONE_CATEGORY_NODE  = ''; // At HomePage navmenu
    const LEVEL_ONE_CATEGORY_CLASS = ''; // At Level One Category Page leftmenu
    const CATEGORY_WRAP_NODE       = '//div[@id=\'tophead\']'; // At HomePage navmenu

    const CURL_FOLLOW = 1; // CURLOPT_FOLLOWLOCATION


    static $region;

    /**
     * @return array
     */
    public function nestCategories(string $parentUrl = '', string $parentTitle = '', int $nestLevel = -1)
    {
    }

    /**
     * @return array
     */
    public function parseCategories()
    {
        $data = [];

        if ($response = $this->sessionClient(self::$model->domain)) {
            if (($nodes = $this->getNodes($response, self::CATEGORY_WRAP_NODE)) && $nodes->length) {
                $tophead = $nodes[0];
                if ($links = $tophead->getElementsByTagName('a')) {
                    foreach ($links as $key => $link) {
                        if ($link->getAttribute('sub')) {
                            $trim = explode('/russia/', $link->getAttribute('href'))[0];
                            $explode = explode('/', $trim);
                            $alias = end($explode);
                            $data[$link->getAttribute('sub')] = [
                                'csid'       => '',
                                'dump'       => '',
                                'alias'      => $alias ?? '',
                                'href'       => $trim,
                                'title'      => trim($link->textContent) ?? '--',
                                'nest_level' => 0,
                            ];

                            foreach ($tophead->getElementsByTagName('div') as $div) {
                                if ($div->getAttribute('sub') == $link->getAttribute('sub')) {
                                    foreach ($div->getElementsByTagName('a') as $child) {
                                        $trm = explode('/russia/', $child->getAttribute('href'))[0];
                                        $exp = explode('/', $trm);
                                        $nick = end($exp);
                                        $data[$link->getAttribute('sub')]['children'][] = [
                                            'csid'       => '',
                                            'dump'       => '',
                                            'alias'      => $nick ?? '',
                                            'href'       => $trm,
                                            'title'      => trim($child->textContent) ?? '--',
                                            'nest_level' => 1,
                                        ];
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

    /**
     * @return array
     */
    public function getWarningData(\DOMNodeList $nodes)
    {
    }




    /**
     * @return
     */
    public static function xpathSale(string $xpath)
    {
        $extend = ' and (contains(translate(string(), \'АКЦИ\', \'акци\'), \'акци\') or .//div[@class=\'super_price\'])';
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
            foreach ($node->getElementsByTagName('*') as $child) {
                if (strpos($child->getAttribute('class'), 'search_container_name') !== false) {
                    $title = $child->childNodes[0];
                }
                if (strpos($child->getAttribute('class'), 'price') !== false) {
                    $price = preg_replace('/[^0-9]/', '', $child->textContent);
                }
            }
            if ($price) {
                $data[] = [
                    'price' => $price,
                    'name'  => $title->textContent,
                    'href'  => self::$model->domain . $title->getAttribute('href'),
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
                'text'  => $node->textContent,
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
        foreach ($object as $node) {
            if ($node->getElementsByTagName('td') 
                && $node->getElementsByTagName('td')->length == 2
                && $node->getElementsByTagName('td')[1]->getElementsByTagName('span')
                && $node->getElementsByTagName('td')[1]->getElementsByTagName('span')->length) {
                    $data[] = [
                        'title' => trim($node->getElementsByTagName('td')[0]->getElementsByTagName('span')[0]->textContent),
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
        foreach ($object as $node) {
            $medium = $node->getAttribute('src');
            $fullsize = str_replace('/medium/', '/big/', $medium);
            $thumb = str_replace('/medium/', '/small/', $medium);
            $data[] = [
                'fullsize' => $fullsize,
                'thumb' => $thumb,
            ];
        }
        return $data;
    }

    public function pageQuery(int $page, string $url)
    {
        $page++;
        $pageQuery = '';

        if (strpos($url, '?') !== false) {
            $pageQuery = '&page=';
        } else {
            $pageQuery = '?page=';
        }

        return $page > 0 ? $pageQuery . $page : '';
    }

    public function urlBuild(string $regionSourceId = '', string $categorySourceId = '', string $keyword = '')
    {
        $domain   = self::$model->domain;
        $region   = Region::findOne($regionSourceId);

        if (!$regionSourceId) {
            $region = Region::find()
                ->select('*')
                ->join('join', 'region_source rs', 'rs.region_id = region.id')
                ->where(['rs.source_id' => self::$model->id])
                ->andWhere(['rs.status' => 2])
                ->one();
        }
        
        $category = CategorySource::findOne($categorySourceId);

        $keyword = str_replace(' ', '+', $keyword);

        $url = '';

        if ($region && !$categorySourceId && !$keyword) {
            $url = $domain . '/' . $region->alias;
        }

        if ($region && $categorySourceId && !$keyword) {
            $url = $this->processUrl($category->source_url);
            // $url = $domain . $category->source_url . $region->alias;
        }

        if ($region && !$categorySourceId && $keyword) {
            $url = $domain . '/' . self::ACTION_SEARCH . '&' . self::QUERY_KEYWORD . $keyword;
        }

        if ($region && $categorySourceId && $keyword) {
            $url = $domain . '/' . self::ACTION_SEARCH . self::QUERY_CATEGORY . $category->source_category_alias . '&' . self::QUERY_KEYWORD . $keyword;
        }

        return $url;
    }

}
