<?php

namespace backend\models\parser\source;

use backend\models\CategorySource;
use backend\models\History;
use backend\models\parser\Parser;
use backend\models\parser\ParserSourceInterface;
use backend\models\Source;

class OzonParser extends Parser implements ParserSourceInterface
{

    const ACTION_SEARCH  = '?context=search';
    const QUERY_CATEGORY = 'group=';
    const QUERY_KEYWORD  = 'text=';

    // const XPATH_WARNING = '//*[contains(@class, \'bNotice\')]'; // At Catalog/Search Page
    const XPATH_WARNING = ''; // At Catalog/Search Page
    const XPATH_CATALOG = '//yml_catalog'; // At Catalog/Search Page
    // const XPATH_CATALOG = '//*[contains(@itemprop, \'itemListElement\')]'; // At Catalog/Search Page

    const XPATH_SUPER = '//*[contains(@class, \'bBaseInfoColumn\')]'; // At Product Page. JS Script with JSON Whole Data Object
    const XPATH_ATTRIBUTE   = '//*[contains(@class, \'eItemProperties_line\')]'; // At Product Page
    const XPATH_DESCRIPTION = '//*[contains(@class, \'eItemDescription_text\')]'; // At Product Page
    const XPATH_IMAGE = '//*[contains(@class, \'bItemMicroGallerys\')]'; // At Product Page. Full size.

    const LEVEL_ONE_CATEGORY_NODE    = '//*[contains(@class, \'eMainMenu_TopLevel\')]//*[@data-ext-menu]'; // At HomePage navmenu
    const LEVEL_TWO_CATEGORY_NODE    = '//*[contains(@class, \'eLeftMainMenu_ElementsBlock\')]'; // At Level One Category Page leftmenu
    const LEVEL_TWO_CATEGORY_CLASS   = 'eLeftMainMenu_Title'; // At Level One Category Page leftmenu
    const LEVEL_THREE_CATEGORY_CLASS = 'eLeftMainMenu_Link'; // At Level One Category Page leftmenu

    const CURL_FOLLOW = 1; // CURLOPT_FOLLOWLOCATION

    const MAX_QUANTITY = 990; // Real range is from 990 to ~1140 (+/-)

    const DEFINE_CLIENT = 'file';


    public function parseCategories()
    {
        $data = [];

        if ($response = $this->sessionClient(self::$model->domain . '/context/partner_xml/')) {
            if (($nodes = $this->getNodes($response, '//a[@download]')) && $nodes->length) {

                $dataNodes = [];
                foreach ($nodes as $nod) {
                    $dataNodes[] = $nod;
                    unset($nod);
                }
                unset($nodes);

                // foreach ($nodes as $key => $node) {
                foreach ($dataNodes as $key => &$node) {
                    $content = file_get_contents('http:' . $node->getAttribute('download'));
                    unset($node);
                    
                    $lines = explode("\n", $content); // Double Qoute
                    unset($content);

                    $dataL1 = $dataL2 = $dataL3 = [];

                    foreach ($lines as $keyLine => &$line) {
                        // Define NEST by counting blank spaces
                        $nest = (strlen($line) - strlen(ltrim($line)) - 2) / 2;
                        $expLine = explode('(', rtrim(trim($line), ')'));
                        unset($line);

                        $title = trim(json_decode(str_replace('\ufeff', '', json_encode($expLine[0])))); // With BOM markups deleted
                        unset($expLine[0]);

                        if ($keyLine == 0) {
                            $data[$key] = [
                                'href'       => $expLine[1],
                                'title'      => $title,
                                'nest_level' => 0,
                            ];
                        }

                        if ($nest == 1) {
                            $data[$key]['children'][$keyLine] = [
                                'href'       => $expLine[1],
                                'title'      => $title,
                                'nest_level' => 1,
                            ];
                            $dataL1[] = $keyLine;
                        }

                        if ($nest == 2) {
                            $data[$key]['children'][end($dataL1)]['children'][$keyLine] = [
                                'href'       => $expLine[1],
                                'title'      => $title,
                                'nest_level' => 2,
                            ];
                            $dataL2[] = $keyLine;
                        }

                        if ($nest == 3) {
                            $data[$key]['children'][end($dataL1)]['children'][end($dataL2)]['children'][$keyLine] = [
                                'href'       => $expLine[1],
                                'title'      => $title,
                                'nest_level' => 3,
                            ];
                            $dataL3[] = $keyLine;
                        }

                        // if ($nest == 4) {
                        //     $data[$key]['children'][end($dataL1)]['children'][end($dataL2)]['children'][end($dataL3)]['children'][$keyLine] = [
                        //         'href'       => $expLine[1],
                        //         'title'      => $title,
                        //         'nest_level' => 4,
                        //     ];
                        // }

                        unset($keyLine, $nest, $expLine, $title);
                    }

                    // $usg = memory_get_peak_usage(true);
                    // print_r('Peak: ' . $usg . PHP_EOL);

                    // if ($key == 3) {
                    //     break;
                    // }

                    unset($key, $node, $lines, $dataL1, $dataL2, $dataL3);
                }
            }
        }

        return $data;
    }


    // public function parseCategories()
    public function parseCategoriesMute()
    {
        $response      = $this->curlSession(self::$model->domain);
        print_r($response);
        $levelOneNodes = $this->getNodes($response, self::LEVEL_ONE_CATEGORY_NODE);

        $data = [];

        // LEVEL ONE Categories

        foreach ($levelOneNodes as $tempKey => $nodeOne) {
            $hrefOne        = explode('?', $nodeOne->getElementsByTagName('*')[0]->getAttribute('href'));
            $hrefOneExplode = explode('/', trim($hrefOne[0], '/'));

            $levelOneData = [
                'dump'  => $hrefOne[1] ?? '',
                'href'  => $nodeOne->getElementsByTagName('a')[0]->getAttribute('href'),
                'alias' => $nodeOne->getAttribute('data-ext-menu'),
                'csid'  => $hrefOne[0] ? (strpos($hrefOne[0], 'catalog') !== false ? $hrefOneExplode[1] : '') : 'NONCAT',
                'title' => trim($nodeOne->textContent),
                'nest_level' => 0,
            ];

            $responseCategory = $this->curlSession(self::$model->domain . $levelOneData['href']);
            $levelTwoNodes    = $this->getNodes($responseCategory, self::LEVEL_TWO_CATEGORY_NODE);

            // LEVEL TWO & THREE Categories

            foreach ($levelTwoNodes as $nodeTwo) {
                $levelTwoData   = [];
                $levelThreeData = [];

                foreach ($nodeTwo->getElementsByTagName('*') as $node) {

                    // LEVEL TWO Categories

                    if (strpos($node->getAttribute('class'), self::LEVEL_TWO_CATEGORY_CLASS) !== false) {
                        $hrefTwo        = explode('?', $nodeTwo->getElementsByTagName('*')[0]->getAttribute('href'));
                        $hrefTwoExplode = explode('/', trim($hrefTwo[0], '/'));
                        $titleTwo = trim($nodeTwo->getElementsByTagName('*')[0]->textContent);

                        $levelTwoData = [
                            'dump'  => $hrefTwo[1] ?? '',
                            'href'  => $hrefTwo[0] ?? '',
                            'alias' => $hrefTwo[0] ? (strpos($hrefTwo[0], 'context') !== false ? $hrefTwoExplode[1] : '') : 'NONCAT',
                            'csid'  => $hrefTwo[0] ? (strpos($hrefTwo[0], 'catalog') !== false ? $hrefTwoExplode[1] : '') : 'NONCAT',
                            'title' => $titleTwo != '' ? $titleTwo : '--',
                            'nest_level' => 1,
                        ];
                    }

                    // LEVEL THREE Categories

                    if (strpos($node->getAttribute('class'), self::LEVEL_THREE_CATEGORY_CLASS) !== false) {
                        $hrefThree        = explode('?', $node->getAttribute('href'));
                        $hrefThreeExplode = explode('/', trim($hrefThree[0], '/'));

                        if ($hrefThree) {
                            $levelThreeData = [
                                'dump'  => $hrefThree[1] ?? '',
                                'href'  => $hrefThree[0] ?? '',
                                'alias' => $hrefThree[0] ? (strpos($hrefThree[0], 'context') !== false ? $hrefThreeExplode[1] : '') : 'NONCAT',
                                'csid'  => $hrefThree[0] ? (strpos($hrefThree[0], 'catalog') !== false ? $hrefThreeExplode[1] : '') : 'NONCAT',
                                'title' => trim($node->textContent ?? '', '>'),
                                'nest_level' => 2,
                            ];
                        }
                    }

                    // LEVEL THREE Nesting

                    if ($levelTwoData && $levelThreeData && $levelTwoData['href'] != $levelThreeData['href']) {
                        $levelTwoData['children'][] = $levelThreeData;
                    }
                }

                // LEVEL TWO Nesting

                $levelOneData['children'][] = $levelTwoData;
            }

            // LEVEL ONE Nesting

            $data[] = $levelOneData;

            // if ($tempKey == 2) {
            //     break;
            // }
            // Do not forget to REMOVE
        }

        return $data;
    }

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
     * Extracting data from the product item's element of a category/search page
     * @return array
     */
    // public function getProducts(\DOMNodeList $nodes)
    public function getProducts($nodes)
    {
        $data = [];
        $images = [];
        $attributes = [];
        $descriptions = [];

        foreach ($nodes->shop->offers->offer as $node) {
            $name = (string)$node->name;

            if (isset($node->vendor)) {
                $name .= '. ' . (string)$node->vendor;
            }
            if (isset($node->author)) {
                $name .= '. ' . (string)$node->author;
            }

            if (isset($node->picture)) {
                foreach ($node->picture as $picture) {
                    $images[] = [
                        'fullsize' => (string)$picture,
                        'thumb' => '',
                    ];
                }
            }

            if (isset($node->param)) {
                foreach ($node->param as $param) {
                    $attributes[] = [
                        'title' => (string)$param['name'],
                        'value' => (string)$param . (string)$param['unit'],
                    ];
                }
            }

            if (isset($node->description)) {
                foreach ($node->description as $desc) {
                    $descriptions[] = [
                        'title' => '',
                        'text' => (string)$desc,
                    ];
                }
            }

            // if ($href) {
                $data[] = [
                    'name'       => $name,
                    'price'      => (string)$node->price,
                    'href'       => explode('/?', (string)$node->url)[0],
                    'descriptions' => $descriptions,
                    'attributes' => $attributes,
                    'images' => $images,
                ];
            // }
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
                    'title' => trim($node->getElementsByTagName('*')[0]->textContent),
                    'value' => trim($node->getElementsByTagName('*')[1]->textContent),
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
                    'fullsize' => $node->getElementsByTagName('img')[0]->getAttribute('src'),
                    'thumb'    => '',
                ];
            }
        }
        return $data;
    }

    public function pageQuery(int $page, string $url)
    {
        $pageQuery = '';

        if (strpos($url, 'context') !== false) {
            $pageQuery = '&page=';
        } elseif (strpos($url, '?') !== false) {
            $pageQuery = '&page=';
        } else {
            $pageQuery = '?page=';
        }

        return $page > 0 ? $pageQuery . $page : '';
    }

    public function urlBuild(string $regionSourceId = '', string $categorySourceId = '', string $keyword = '')
    {
        $domain   = self::$model->domain . '/';
        $category = CategorySource::findOne($categorySourceId);

        $keyword = str_replace(' ', '+', $keyword);

        $url = '';

        // if ($categorySourceId != null && $keyword != '') {
        //     if ($category->source_category_alias) {
        //         $url = $domain . self::ACTION_SEARCH . '&' . self::QUERY_CATEGORY . $category->source_category_alias . '&' . self::QUERY_KEYWORD . $keyword;
        //     } else {
        //         $url = $this->processUrl($category->source_url) . '?' . self::QUERY_KEYWORD . $keyword;
        //     }
        // }

        if ($categorySourceId != null && $keyword == '') {
            // if ($category->source_category_alias) {
            //     $url = $domain . self::ACTION_SEARCH . '&' . self::QUERY_CATEGORY . $category->source_category_alias;
            // } else {
                // $url = $this->processUrl($category->source_url);
                $url = $category->source_url;
            // }
        }

        // if ($categorySourceId == null && $keyword != '') {
        //     $url = $domain . self::ACTION_SEARCH . '&' . self::QUERY_KEYWORD . $keyword;
        // }

        return $url;
    }

}
