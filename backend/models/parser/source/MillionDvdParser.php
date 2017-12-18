<?php

namespace backend\models\parser\source;

use backend\models\CategorySource;
use backend\models\History;
use backend\models\parser\Parser;
use backend\models\parser\ParserSourceInterface;
use backend\models\Source;

class MilliondvdParser extends Parser implements ParserSourceInterface
{

    const ACTION_SEARCH  = 'search?title=0';
    const QUERY_CATEGORY = 'sclass=';
    const QUERY_KEYWORD  = 'text=';

    const XPATH_WARNING = '//table[contains(@style, \'position: relative;\')][string-length() = 0]'; // At Catalog/Search Page
    const XPATH_CATALOG = '//table[contains(@style, \'position: relative;\')]//tr'; // At Catalog/Search Page

    const XPATH_SUPER = '//notelement'; // At Product Page. JS Script with JSON Whole Data Object
    const XPATH_ATTRIBUTE   = '//div//span[contains(@style, \'color:silver\')]'; // At Product Page
    const XPATH_DESCRIPTION   = '//div[contains(@style, \'border-top: 1px solid silver\')]'; // At Product Page
    // const XPATH_DESCRIPTION = '//div[contains(@class, \'yashare-auto-init\')]'; // At Product Page
    const XPATH_IMAGE = '//a[@data-lightbox]'; // At Product Page. Full size.

    const LEVEL_ONE_CATEGORY_NODE    = '//td[contains(@class, \'tbg1\')]//a[not(contains(@href, \'://\'))]'; // At HomePage sidebar

    const CURL_FOLLOW = 0; // CURLOPT_FOLLOWLOCATION


    public function parseCategories()
    {
        $response = $this->curlSession(self::$model->domain);
        $links = $this->getNodes($response, self::LEVEL_ONE_CATEGORY_NODE);

        $data = [];
        $tempData = [];

        foreach ($links as $link) {
            $tempData[] = $link;
        }

        foreach ($tempData as $keyOne => $linkOne) {
            if ($linkOne->parentNode->nodeName == 'div') {
                $data[] = [
                    'key' => $keyOne,
                    'alias' => '',
                    'dump'  => '',
                    'csid'  => '',
                    'href'  => $this->processUrl($linkOne->getAttribute('href')),
                    'title' => trim($linkOne->textContent),
                    'nest_level'  => 0,
                ];
            }
        }

        foreach ($data as $key => $parent) {
            foreach ($tempData as $keyTwo => $linkTwo) {

                if ($linkTwo->parentNode->nodeName == 'li'
                    && $keyTwo > $data[$key]['key']
                    && (isset($data[$key + 1]) ? ($keyTwo < $data[$key + 1]['key']) : true) ) 
                {
                        $data[$key]['children'][] = [
                            'alias' => '',
                            'dump'  => '',
                            'csid'  => '',
                            'href'  => $this->processUrl($linkTwo->getAttribute('href')),
                            'title' => trim($linkTwo->textContent),
                            'nest_level'  => 1,
                        ];
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
        $extend = '[.//span[contains(@style, \'line-through\')]]';
        $xpath = $xpath . $extend;

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
            $link  = $node->getElementsByTagName('a')[1]->getAttribute('href');
            $linkExplode = explode ('/',$link);
            $id    = $linkExplode[2];
            $price = trim($node->getElementsByTagName('b')[1]->textContent);
            $name  = trim($node->getElementsByTagName('a')[1]->textContent);
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
        foreach ($object as $node) {
            $innerHTML = '';
            $children = $node->childNodes;
            foreach ($children as $child) {
                $innerHTML .= $child->ownerDocument->saveXML( $child );
            }
            $data[] = [
                'title' => '',
                'text'  => $innerHTML,
            ];
        }
        // return $data;


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
                'title' => trim($node->textContent),
                'value' => trim($node->parentNode->getElementsByTagName('span')[1]->textContent),
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
                'fullsize' => $node->getAttribute('href'),
                'thumb'    => $node->getElementsByTagName('img')[0]->getAttribute('src'),
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
        $model    = self::$model;
        $domain   = self::$model->domain . '/';
        $category = CategorySource::findOne($categorySourceId);

        $keyword = str_replace(' ', '+', $keyword);

        $url = '';

        if ($categorySourceId != null && $keyword != '') {
            if ($category->source_category_alias) {
                $url = $domain . self::ACTION_SEARCH . '&' . self::QUERY_CATEGORY . $category->source_category_alias . '&' . self::QUERY_KEYWORD . $keyword;
            } else {
                $url = $category->source_url . '?' . self::QUERY_KEYWORD . $keyword;
            }
        }

        if ($categorySourceId != null && $keyword == '') {
            if ($category->source_category_alias) {
                $url = $domain . self::ACTION_SEARCH . '&' . self::QUERY_CATEGORY . $category->source_category_alias;
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
