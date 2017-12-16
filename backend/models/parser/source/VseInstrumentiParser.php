<?php

namespace backend\models\parser\source;

use backend\models\parser\ParserSourceInterface;
use backend\models\parser\ParserProvisioner;
use backend\models\parser\Parser;
use backend\models\CategorySource;
use backend\models\Region;
use backend\models\Source;


class VseInstrumentiParser extends Parser implements ParserSourceInterface
{
    const ACTION_SEARCH  = 'search_main.php?';
    const QUERY_CATEGORY = '';
    const QUERY_KEYWORD  = 'what=';

    const XPATH_WARNING = ''; // At Catalog/Search Page
    const XPATH_CATALOG = '//div[@id=\'goodsListingBox\']//div[@data-good-id]'; // At Catalog/Search Page

    const XPATH_SUPER       = ''; // At Product Page. JS Script with JSON Whole Data Object
    const XPATH_ATTRIBUTE   = '//div[@itemprop=\'additionalProperty\']'; // At Product Page
    const XPATH_DESCRIPTION = '//div[@id=\'tab1_content\']//div[@itemprop=\'description\']'; // At Product Page
    const XPATH_IMAGE       = '//div[@data-type=\'imageGoods\']'; // At Product Page. Full size.

    // const CATEGORY_NODE  = ''; // At HomePage navmenu
    const CATEGORY_WRAP_NODE  = '//ul[@id=\'nav\']//a[@data-cat-id]'; // At HomePage navmenu
    // const CATEGORY_WRAP_CLASS = 'catalog-subcatalog'; // At Level One Category Page leftmenu

    const CURL_FOLLOW = 1; // CURLOPT_FOLLOWLOCATION

    const MAX_QUANTITY = '';

    const DEFINE_CLIENT = 'curl'; // CURLOPT_FOLLOWLOCATION

    static $region;
    static $template;

    /**
     * @return array
     */
    public function parseCategories()
    {
        $data = [];
        $dataTwo = [];

        if ($response = $this->sessionClient(self::$model->domain)) {
            if (($nodes = $this->getNodes($response, self::CATEGORY_WRAP_NODE)) && $nodes->length) {
                foreach ($nodes as $key => $node) {
                    if ($node->getAttribute('data-cat-id') == $node->getAttribute('data-parent-id')) {
                        $data[$node->getAttribute('data-cat-id')] = [
                            'csid'       => $node->getAttribute('data-cat-id'),
                            'dump'       => '',
                            'alias'      => '',
                            'href'       => $this->processUrl($node->getAttribute('href')),
                            'title'      => trim($node->textContent) ?? '--',
                            'nest_level' => 0,
                        ];
                    }
                    if ($node->parentNode->parentNode->getAttribute('class') == 'level3') {
                        $dataTwo[$node->getAttribute('data-cat-id')] = [
                            'parent'     => $node->getAttribute('data-parent-id'),
                            'csid'       => $node->getAttribute('data-cat-id'),
                            'dump'       => '',
                            'alias'      => '',
                            'href'       => $this->processUrl($node->getAttribute('href')),
                            'title'      => trim($node->textContent) ?? '--',
                            'nest_level' => 1,
                        ];
                    }
                    if ($node->parentNode->parentNode->getAttribute('class') == 'childs'
                        && isset($dataTwo[$node->getAttribute('data-parent-id')])) {
                            $dataTwo[$node->getAttribute('data-parent-id')]['children'][$node->getAttribute('data-cat-id')] = [
                                'csid'       => $node->getAttribute('data-cat-id'),
                                'dump'       => '',
                                'alias'      => '',
                                'href'       => $this->processUrl($node->getAttribute('href')),
                                'title'      => trim($node->textContent) ?? '--',
                                'nest_level' => 2,
                            ];
                    }
                }
                foreach ($dataTwo as $key => $two) {
                    if ($two && isset($two['parent'])) {
                        $data[$two['parent']]['children'][] = $two;
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
        foreach ($nodes as $node) {
            foreach ($node->getElementsByTagName('*') as $child) {
                if (strpos($child->getAttribute('class'), 'product-name') !== false) {
                    $title = $child->getElementsByTagName('a')[0];
                }
                if (strpos($child->getAttribute('class'), 'price-actual') !== false) {
                    $price = preg_replace('/[^0-9]/', '', $child->textContent);
                }
            }
            if ($title->getAttribute('href') && isset($price)) {
                $data[] = [
                    'price' => $price,
                    'name'  => $title->textContent,
                    'href'  => $this->processUrl($title->getAttribute('href')),
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
                'text' => $node->textContent,
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
            $data[] = [
                'title' => $node->getElementsByTagName('span')[0]->textContent,
                'value' => $node->getElementsByTagName('span')[1]->textContent,
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
        foreach ($object as $node) {
            $data[] = [
                'fullsize' => $node->getAttribute('data-src-source'),
                'thumb' => '', // Thumbs are actually original images
            ];
        }
        return $data;
    }

    public function pageQuery(int $page, string $url)
    {
        $page++;
        $pageQuery = '';

        if (self::$template == 'search') {
            $pageQuery = '&page=';
        } else {
            $pageQuery = 'page';
        }

        $returnPage = $page > 1 ? $pageQuery . $page : '';

        return $returnPage;
    }

    public function urlBuild(string $regionId = '', string $categorySourceId = '', string $keyword = '')
    {
        $category = CategorySource::findOne($categorySourceId);
        $keyword = str_replace(' ', '+', $keyword);

        if ($categorySourceId && !$keyword) {
            self::$template = 'catalog';
            if (self::$model->saleFlag === true) {
                $exp = explode(self::$model->domain, $category->source_url);
                return $this->processUrl('/rasprodazha' . end($exp));
            } else {
                return $this->processUrl($category->source_url);
            }
        }
        if (!$categorySourceId && $keyword) {
            self::$template = 'search';
            return self::$model->domain . '/' . self::ACTION_SEARCH . self::QUERY_KEYWORD . $keyword;
        }
    }
}
