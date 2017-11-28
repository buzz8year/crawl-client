<?php

namespace backend\models\parser\source;

use backend\models\parser\ParserSourceInterface;
use backend\models\parser\ParserProvisioner;
use backend\models\parser\Parser;
use backend\models\CategorySource;
use backend\models\Region;
use backend\models\Source;


class PiterGsmParser extends Parser implements ParserSourceInterface
{
    const ACTION_SEARCH  = 'search/index.php?';
    const QUERY_CATEGORY = '';
    const QUERY_KEYWORD  = 'q=';

    const XPATH_WARNING = ''; // At Catalog/Search Page
    const XPATH_CATALOG = '//table[@id=\'cat-list\']//div[@class=\'inner\']'; // At Catalog/Search Page
    const XPATH_SEARCH  = '//div[@class=\'search-page\']/a';

    const XPATH_SUPER       = ''; // At Product Page. JS Script with JSON Whole Data Object
    const XPATH_ATTRIBUTE   = '//div[@class=\'features\']'; // At Product Page
    const XPATH_DESCRIPTION = '//div[@class=\'preview\']'; // At Product Page
    const XPATH_IMAGE       = '//div[@class=\'image\']/a'; // At Product Page. Full size.
    const XPATH_PRICE       = '//div[@class=\'price\']'; // At Product Page

    const CATEGORY_NODE  = '//div[@class=\'sidebar-left\']//div[@class=\'inner\']'; // At HomePage navmenu
    // const CATEGORY_WRAP_NODE  = '//*[contains(@class, \'sub-wrap\')]'; // At HomePage navmenu
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

        if ($response = $this->sessionClient(self::$model->domain)) {
            if (($nodes = $this->getNodes($response, self::CATEGORY_NODE)) && $nodes->length) {
                foreach ($nodes as $key => $node) {
                    foreach ($node->getElementsByTagName('*') as $element) {
                        if ($element->nodeName == 'h4') {
                            $data[$key] = [
                                'csid'       => '',
                                'dump'       => '',
                                'alias'      => '',
                                'href'       => '',
                                'title'      => trim($element->textContent),
                                'nest_level' => 0,
                            ];
                        }
                        if ($element->nodeName == 'a') {
                            $data[$key]['children'][] = [
                                'csid'       => '',
                                'dump'       => '',
                                'alias'      => '',
                                'href'       => self::$model->domain . $element->getAttribute('href'),
                                'title'      => trim($element->textContent),
                                'nest_level' => 1,
                            ];
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
     * Extracting data from the product item's element of a category/search page
     * @return array
     */
    public function getProducts(\DOMNodeList $nodes)
    {
        $data = [];

        if (self::$template == 'search') {
            foreach ($nodes as $node) {
                if (explode('/', $node->getAttribute('href'))[1] == 'e-store') {
                    $href = self::$model->domain . $node->getAttribute('href');
                    if ($response = $this->parse('price', $href)) {
                        $price = preg_replace('/[^0-9]/', '', $response);
                    }
                    $data[] = [
                        'price' => $price ?? null,
                        'name'  => $node->textContent,
                        'href'  => $href,
                    ];
                }
            }
        } else {
            foreach ($nodes as $node) {
                foreach ($node->getElementsByTagName('div') as $div) {
                    if ($div->getAttribute('class') == 'title') {
                        $title = $div->textContent;
                        $href  = $div->getElementsByTagName('a')[0]->getAttribute('href');
                    }
                    if ($div->getAttribute('class') == 'price') {
                        $price = preg_replace('/[^0-9]/', '', $div->textContent);
                    }
                }
                    $data[] = [
                        'price' => $price ?? null,
                        'name'  => $title,
                        'href'  => self::$model->domain . $href,
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
        if ($object[0]->getElementsByTagName('table')->length) {
            foreach ($object[0]->getElementsByTagName('*') as $key => $element) {
                if ($element->getAttribute('class') == 'inner') {
                    foreach ($element->childNodes as $child) {
                        if ($child->nodeName == 'h3') {
                            $data[$key]['title'] = $child->textContent;
                        }
                        if ($child->nodeName == 'p') {
                            $data[$key]['value'] = $child->textContent;
                        }
                    }
                }
            }
        } else {
            $data[] = [
                'title' => '',
                'value'  => $object[0]->textContent,
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
                'fullsize' => self::$model->domain . $node->getAttribute('href'),
                'thumb'  => self::$model->domain . $node->getElementsByTagName('img')[0]->getAttribute('src'),
            ];
        }
        return $data;
    }

    public function getPriceData($object)
    {
        $data = $object->length ? $object[0]->textContent : '';
        return $data;
    }

    public function pageQuery(int $page, string $url)
    {
        return '';
    }

    public function urlBuild(string $regionSourceId = '', string $categorySourceId = '', string $keyword = '')
    {
        $category = CategorySource::findOne($categorySourceId);

        $keyword = str_replace(' ', '+', $keyword);

        $url = '';

        if ($categorySourceId && !$keyword) {
            $url = $category->source_url;
            self::$template = 'catalog';
        }

        // if ($categorySourceId && $keyword) {
        //     $url = $domain . $category->source_url . '?' . self::QUERY_KEYWORD . $keyword;
        // }

        if (!$categorySourceId && $keyword) {
            $url = self::$model->domain . '/' . self::ACTION_SEARCH . self::QUERY_KEYWORD . $keyword;
            self::$template = 'search';
        }

        return $url;
    }

}
