<?php

namespace crawler\parser\impl;

use crawler\parser\ParserSourceInterface;
use crawler\parser\ParserProvisioner;
use crawler\parser\Parser;
use crawler\models\CategorySource;
use crawler\models\Region;
use crawler\models\source\Source;


class LitresParser extends Parser implements ParserSourceInterface
{
    const ACTION_SEARCH  = 'pages/biblio_search/?';
    const QUERY_CATEGORY = '';
    const QUERY_KEYWORD  = 'q=';

    const XPATH_WARNING = ''; // At Catalog/Search Page
    const XPATH_CATALOG = '//div[@class=\'art-item\']'; // At Catalog/Search Page
    const XPATH_SEARCH = '//div[contains(@class, \'tab-item t6\')]/div[contains(@class, \'newbook\')]'; // At Catalog/Search Page

    const XPATH_SUPER       = ''; // At Product Page. JS Script with JSON Whole Data Object
    const XPATH_ATTRIBUTE   = '//ul[@class=\'biblio_book_info_detailed_left\']/li'; // At Product Page
    const XPATH_DESCRIPTION = '//div[@itemprop=\'description\']'; // At Product Page
    const XPATH_IMAGE       = '//img[@itemprop=\'image\']'; // At Product Page. Full size.

    const CATEGORY_NODE  = '//*[@class=\'genre\']'; // At HomePage navmenu
    // const CATEGORY_WRAP_NODE  = '//*[contains(@class, \'sub-wrap\')]'; // At HomePage navmenu
    // const CATEGORY_WRAP_CLASS = 'catalog-subcatalog'; // At Level One Category Page leftmenu

    const CURL_FOLLOW = 0; // CURLOPT_FOLLOWLOCATION

    const MAX_QUANTITY = '';

    const DEFINE_CLIENT = 'curl'; // CURLOPT_FOLLOWLOCATION

    const PAGER_SPLIT_URL = false;

    static $region;
    static $template;

    /**
     * @return array
     */
    public function parseCategories()
    {
        $data = [];

        if ($response = $this->sessionClient(self::$model->domain . '/pages/new_genres/')) {
            // print_r($response);
            if (($nodes = $this->getNodes($response, self::CATEGORY_NODE)) && $nodes->length) {
                print_r($nodes);
                foreach ($nodes as $key => $node) {
                    // echo($node->getElementsByTagName('span')[0]->textContent . '<br/>');
                    $top = $node->getElementsByTagName('span')[0]->getElementsByTagName('a')[0];
                    $data[$key] = [
                        'csid'       => '',
                        'dump'       => '',
                        'alias'      => '',
                        'href'       => $this->handleUrl($top->getAttribute('href')),
                        'title'      => trim($top->textContent),
                        'nest_level' => 0,
                    ];

                    if (($lis = $node->getElementsByTagName('ul')) && $lis->length && $lis[0]->getElementsByTagName('li')->length) {
                        foreach ($lis[0]->getElementsByTagName('li') as $liKey => $li) {
                            if ($li->parentNode === $lis[0]) {
                                $sub = $li->getElementsByTagName('span')[0]->getElementsByTagName('a')[0];
                                if ($sub) {
                                    $data[$key]['children'][$liKey] = [
                                        'csid'       => '',
                                        'dump'       => '',
                                        'alias'      => '',
                                        'href'       => $this->handleUrl($sub->getAttribute('href')),
                                        'title'      => trim(preg_replace('/[0-9]/', '', $sub->textContent)),
                                        'nest_level' => 1,
                                    ];
                                }

                                // if (($sublis = $li->getElementsByTagName('ul')) && $sublis->length && $sublis[0]->getElementsByTagName('li')->length) {
                                //     foreach ($sublis[0]->getElementsByTagName('li') as $subliKey => $subli) {
                                //         if ($subli->parentNode === $sublis[0]) {
                                //             $subSub = $subli->getElementsByTagName('span')[0]->getElementsByTagName('a')[0];
                                //             $data[$key]['children'][$liKey]['children'][$subliKey] = [
                                //                 'csid'       => '',
                                //                 'dump'       => '',
                                //                 'alias'      => '',
                                //                 'href'       => $subSub->getAttribute('href'),
                                //                 'title'      => trim($subSub->textContent),
                                //                 'nest_level' => 2,
                                //             ];

                                //             if (($subSubLis = $subli->getElementsByTagName('ul')) && $subSubLis->length && $subSubLis[0]->getElementsByTagName('li')->length) {
                                //                 foreach ($subSubLis[0]->getElementsByTagName('li') as $subSubLiKey => $subSubLi) {
                                //                     if ($subSubLi->parentNode === $subSubLis[0]) {
                                //                         $subSubSub = $subSubLi->getElementsByTagName('span')[0]->getElementsByTagName('a')[0];
                                //                         $data[$key]['children'][$liKey]['children'][$subliKey]['children'][] = [
                                //                             'csid'       => '',
                                //                             'dump'       => '',
                                //                             'alias'      => '',
                                //                             'href'       => $subSubSub->getAttribute('href'),
                                //                             'title'      => trim($subSubSub->textContent),
                                //                             'nest_level' => 3,
                                //                         ];
                                //                     }
                                //                 }
                                //             }
                                //         }
                                //     }
                                // }
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
     * Extracting data from the product item's element of a category/search page
     * @return array
     */
    // public function getProducts(\DOMNodeList $nodes)
    public function getProducts($nodes)
    {
        $data = [];
        foreach ($nodes as $node) {
            // $price = $title = $author = $href = '';
            foreach ($node->getElementsByTagName('*') as $child) {
                if (self::$template == 'search') {
                    if ($child->getAttribute('class') == 'ellT2 ell_new') {
                        $href = $child->getElementsByTagName('a')[0]->getAttribute('href');
                        $title = $child->textContent;
                    }
                    if (strpos($child->getAttribute('class'), 'ellA1 ell_new') !== false) {
                        $author = $child->textContent;
                    }
                    if (strpos($child->getAttribute('class'), 'finalprice') !== false) {
                        $price = floatval(str_replace(',', '.', $child->textContent));
                    }
                } else {
                    if ($child->getAttribute('itemprop') == 'name') {
                        $title = $child->textContent;
                        $href  = $child->getAttribute('href');
                    }
                    if ($child->getAttribute('itemprop') == 'author') {
                        $author = $child->textContent;
                    }
                    if ($child->getAttribute('class') == 'simple-price') {
                        $price = floatval(str_replace(',', '.', $child->textContent));
                    }
                }
            }
            $data[] = [
                'price' => $price ?? null,
                'name'  => trim($title) . '. ' . trim($author),
                'href'  => self::$model->domain . $href,
            ];
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
            $exp = explode(': ', $node->textContent);
            $data[] = [
                'title' => trim($exp[0]),
                'value' => trim($exp[1]),
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
                'fullsize' => $node->getAttribute('data-original-image-url'),
                'thumb' => '',
            ];
        }
        return $data;
    }

    public function pageQuery(int $page, string $url)
    {
        $page++;
        $pageQuery = '';

        if (self::$template == 'search') {
            $pageQuery = false;
        } else {
            $pageQuery = 'page-';
        }


        return $page > 1 && $pageQuery ? $pageQuery . $page : '';
    }

    public function buildUrl(string $regionSourceId = '', string $categorySourceId = '', string $keyword = '')
    {
        $domain   = self::$model->domain;
        $category = CategorySource::findOne($categorySourceId);

        $url = '';

        if ($categorySourceId && !$keyword) {
            $url = $this->handleUrl($category->source_url);
            self::$template = 'catalog';
        }

        if ($categorySourceId && $keyword) {
            $url = $domain . '/' . self::ACTION_SEARCH . self::QUERY_CATEGORY . $category->source_category_id . '&' . self::QUERY_KEYWORD . $keyword;
            self::$template = 'search';
        }

        if (!$categorySourceId && $keyword) {
            $url = $domain . '/' . self::ACTION_SEARCH . '&' . self::QUERY_KEYWORD . $keyword;
            self::$template = 'search';
        }

        return $url;
    }

}
