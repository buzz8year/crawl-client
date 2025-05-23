<?php

namespace crawler\models\parser\source;

use crawler\models\parser\ParserSourceInterface;
use crawler\models\parser\ParserProvisioner;
use crawler\models\parser\Parser;
use crawler\models\CategorySource;
use crawler\models\Region;
use crawler\models\source\Source;


class CitiLinkParser extends Parser implements ParserSourceInterface
{
    const ACTION_SEARCH  = 'search/?';
    const QUERY_CATEGORY = 'menu_id=';
    const QUERY_KEYWORD  = 'text=';

    const QUERY_INSTOCK  = 'available=1';
    const QUERY_SALE     = 'status=468661653';

    const XPATH_WARNING = ''; // At Catalog/Search Page
    const XPATH_PAGER   = '//div[@class=\'page_listing\']//a[@data-page]'; // Pagination
    const XPATH_CATALOG = '//div[@class=\'product_category_list\']//div[@data-list-id=\'main\']'; // At Catalog/Search Page

    const XPATH_SUPER       = ''; // At Product Page. JS Script with JSON Whole Data Object
    const XPATH_ATTRIBUTE   = '//table[@class=\'product_features\']//tr'; // At Product Page
    const XPATH_DESCRIPTION = '//p[@class=\'short_description\']'; // At Product Page
    const XPATH_IMAGE       = '//a[@class=\'photo_carousel_link__js\']'; // At Product Page. Full size.

    const CATEGORY_NODE  = '//*[@class=\'menu menu_categories\']//li[contains(@class, \'menu-item_products\')]'; // At HomePage navmenu
    // const CATEGORY_WRAP_NODE  = '//*[contains(@class, \'sub-wrap\')]'; // At HomePage navmenu
    // const CATEGORY_WRAP_CLASS = 'catalog-subcatalog'; // At Level One Category Page leftmenu

    // const PAGER_EXCUSE = 1;

    // const DEFINE_CLIENT = 'phantom'; // CURLOPT_FOLLOWLOCATION
    const CURL_FOLLOW = 0; // CURLOPT_FOLLOWLOCATION

    static $region;

    /**
     * @return array
     */
    public function parseCategories()
    {
        $data = [];

        if ($response = $this->sessionClient(self::$model->domain)) {
            if (($nodes = $this->getNodes($response, self::CATEGORY_NODE)) && $nodes->length) {
                foreach ($nodes as $key => $node) {
                    foreach ($node->getElementsByTagName('a') as $link) {
                        if ($link->getAttribute('href')) {
                            if (trim($link->getAttribute('class')) == 'link_side-menu') {
                                $data[$key] = [
                                    'csid'       => '',
                                    'dump'       => '',
                                    'alias'      => '',
                                    'href'       => $link->getAttribute('href'),
                                    'title'      => trim($link->textContent),
                                    'nest_level' => 0,
                                ];
                            }
                            if (strpos($link->getAttribute('class'), 'subcategory-list-item__link') !== false) {
                                $data[$key]['children'][] = [
                                    'csid'       => '',
                                    'dump'       => '',
                                    'alias'      => '',
                                    'href'       => $link->getAttribute('href'),
                                    'title'      => trim($link->textContent),
                                    'nest_level' => 1,
                                ];
                            }
                        }
                    }
                }
            }
        }

        usort($data, function($a, $b) {
            return (count($b['children']) - count($a['children']));
        });

        return $data;
    }

    /**
     * @return array
     */
    public function getWarningData(\DOMNodeList $nodes)
    {
    }


    /**
     * @return int
     */
    public static function lastPage($nodes)
    {       
        foreach ($nodes as $key => $node) {
            if ($key == ($nodes->length - 1)) {
                return (int)$node->getAttribute('data-page');
            }
        }
    }




    // /**
    //  * @return string
    //  */
    // public static function xpathSale(string $xpath)
    // {
    //     $extend = ' and (
    //         contains(translate(string(), \'SALE\', \'sale\'), \'sale\') or
    //         contains(translate(string(), \'АКЦИ\', \'акци\'), \'акци\') or
    //         contains(translate(string(), \'СКИДК\', \'cкидк\'), \'cкидк\') or
    //         contains(translate(string(), \'РАСПРОДАЖ\', \'распродаж\'), \'распродаж\')
    //     )';
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

        foreach ($nodes as $key => $node) {
            // if ($key < 3) {
            //     print_r($node);
            // }
            $params = json_decode($node->getAttribute('data-params'), true);
            $href = $node->getElementsByTagName('a')[0]->getAttribute('href');
            if ($href) {
                $data[] = [
                    'price' => $params['price'],
                    'name'  => $params['shortName'],
                    'href'  => $this->processUrl($href),
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
        foreach ($object as $key => $node) {
            if ($node->getElementsByTagName('td') && $node->getElementsByTagName('td')->length) {
                $data[$key]['title'] = $node->getElementsByTagName('th')[0]->getElementsByTagName('span')[0]->textContent;
                $data[$key]['value'] = $node->getElementsByTagName('td')[0]->textContent;
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
            $data[] = [
                'fullsize' => $node->getAttribute('href'),
                'thumb'    => $node->getElementsByTagName('img')[0]->getAttribute('src')
            ];
        }
        return $data;
    }

    public function pageQuery(int $page, string $url)
    {
        $page++;
        $pageQuery = '';

        if (strpos($url, '?') !== false) {
            $pageQuery = '&p=';
        } else {
            $pageQuery = '?p=';
        }

        return $pageQuery . $page;
    }

    public function urlBuild(string $regionSourceId = '', string $categorySourceId = '', string $keyword = '')
    {
        $category = CategorySource::findOne($categorySourceId);

        $keyword = str_replace(' ', '+', $keyword);

        $url = '';

        if ($categorySourceId && !$keyword) {
            // $url = $this->processUrl($category->source_url);
            $url = $this->processUrl($category->source_url) . '?' . self::QUERY_INSTOCK;
            if (self::$model->saleFlag === true) {
                $url .= '&' . self::QUERY_SALE;
            }
        }

        // if ($categorySourceId && $keyword) {
        //     $url = $domain . $category->source_url . self::ACTION_SEARCH . self::QUERY_KEYWORD . $keyword;
        // }

        if (!$categorySourceId && $keyword) {
            $url = self::$model->domain . '/' . self::ACTION_SEARCH . self::QUERY_KEYWORD . $keyword;
        }

        // return $url . '?' . self::QUERY_INSTOCK . 1;
        return $url;
    }

}
