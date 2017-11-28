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
    const XPATH_DESCRIPTION = '//div[contains(@class, \'yashare-auto-init\')]'; // At Product Page
    const XPATH_IMAGE = '//a[@data-lightbox]'; // At Product Page. Full size.

    const LEVEL_ONE_CATEGORY_NODE    = '//*[contains(@class, \'sidebar_menu_header\')]//*[@href]'; // At HomePage sidebar
    const LEVEL_TWO_CATEGORY_NODE    = '//*[contains(@class, \'data\')]//*[@href]'; // At Level One Category Page breadcrumb
    const LEVEL_THREE_CATEGORY_NODE  = '//*[contains(@class, \'data\')]//*[@href]'; // At Level Two Category Page breadcrumb

    const CURL_FOLLOW = 0; // CURLOPT_FOLLOWLOCATION


    public function parseCategories()
    {
        $response      = $this->curlSession(self::$model->domain);
        $levelOneNodes = $this->getNodes($response, self::LEVEL_ONE_CATEGORY_NODE);

        $data = [];

        // LEVEL ONE Categories

        foreach ($levelOneNodes as $tempKey => $nodeOne) {
            if ( substr ( $nodeOne->getAttribute('href') , 0 , 1 ) == '/' ) {
                $hrefOneExplode = explode('/', $nodeOne->getAttribute('href'));
                $levelOneData = [
                    'dump'  => '',
                    'href'  => self::$model->domain . $nodeOne->getAttribute('href'),
                    'alias' => $hrefOneExplode[2],
                    'csid'  => '',
                    'title' => trim($nodeOne->textContent),
                ];

                $responseCategory = $this->curlSession($levelOneData['href']);
                $levelTwoNodes    = $this->getNodes($responseCategory, self::LEVEL_TWO_CATEGORY_NODE);

                // LEVEL TWO Categories

                foreach ($levelTwoNodes as $nodeTwo) {
                    $levelTwoData = [
                        'dump'  => '',
                        'href'  => self::$model->domain . $nodeTwo->getAttribute('href'),
                        'alias' => $hrefOneExplode[2],
                        'csid'  => '',
                        'title' => $levelOneData['title'] . " > " . trim($nodeTwo->textContent),
                    ];

                    $responseSubCategory = $this->curlSession($levelTwoData['href']);
                    $levelThreeNodes     = $this->getNodes($responseSubCategory, self::LEVEL_THREE_CATEGORY_NODE);

                    // LEVEL THREE Categories

                    foreach ($levelThreeNodes as $nodeThree) {
                        if ( $nodeThree->parentNode->parentNode->getElementsByTagName('p')->length == 2 )
                            break;
                        
                        $levelThreeData = [
                            'dump'  => '',
                            'href'  => self::$model->domain . $nodeThree->getAttribute('href'),
                            'alias' => $hrefOneExplode[2],
                            'csid'  => '',
                            'title' => $levelOneData['title'] . " > " . $levelTwoData['title'] . " > " . trim($nodeThree->textContent),
                        ];

                        // LEVEL THREE Nesting

                        if ($levelTwoData && $levelThreeData && $levelTwoData['href'] != $levelThreeData['href']) {
                            $levelTwoData['children'][] = $levelThreeData;
                        }
                    }

                    // LEVEL TWO Nesting

                    $levelOneData['children'][] = $levelTwoData;
                }
            }
            // LEVEL ONE Nesting

            $data[] = $levelOneData;

            if ($tempKey == 2) {
                break;
            }
            // Do not forget to REMOVE
        }
        return $data;
    }

    /**
     * @return array
     */

    public function getWarningData(\DOMNodeList $nodes)
    {
        foreach ($nodes as $node) {
            $data[] = 'Не нашли что нужно?';
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
                    'text'  => trim($node->getAttribute('data-yashareDescription')),
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
                    'title' => trim($node->textContent),
                    'value' => trim($node->parentNode->getElementsByTagName('span')[1]->textContent),
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
        $pageQuery = '&page=';
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
