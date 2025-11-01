<?php

namespace crawler\parser\impl;

use crawler\parser\ParserSourceInterface;
use crawler\parser\Parser;
use crawler\models\CategorySource;
use crawler\models\Region;
use crawler\models\source\Source;

class AvitoParser extends Parser implements ParserSourceInterface
{
    public const string ACTION_SEARCH  = '?s=101';
    public const string QUERY_CATEGORY = '';
    public const string QUERY_KEYWORD  = 'q=';

    public const string XPATH_WARNING = ''; // At Catalog/Search Page
    public const string XPATH_CATALOG = '//*[contains(@class, \'item_table-header\')]'; // At Catalog/Search Page

    public const string XPATH_SUPER = ''; // At Product Page. JS Script with JSON Whole Data Object
    public const string XPATH_ATTRIBUTE = ''; // At Product Page
    public const string XPATH_DESCRIPTION = '//*[contains(@class, \'item-description-text\')]'; // At Product Page
    public const string XPATH_IMAGE = '//*[contains(@class, \'gallery-extended-img-frame\')]'; // At Product Page. Full size.

    public const string LEVEL_ONE_CATEGORY_NODE  = ''; // At HomePage navmenu
    public const string LEVEL_ONE_CATEGORY_CLASS = 'root-category'; // At Level One Category Page leftmenu
    public const string CATEGORY_WRAP_NODE = '//div[@class=\'category-map\']/dl'; // At HomePage navmenu

    public const int CURL_FOLLOW = 1; // CURLOPT_FOLLOWLOCATION


    public static $region;

    /**
     * @return array
     */
    public function nestCategories(string $parentUrl = '', string $parentTitle = '', int $nestLevel = -1, int $sleepKey = 0)
    {
        $data = [];

        if ($response = $this->curlSession(self::$model->domain . '/map')) {
            if ($wrappers = $this->getNodes($response, self::CATEGORY_WRAP_NODE)) {
                print_r($wrappers);
                foreach ($wrappers as $keyZero => $wrapZero) {
                    $zero = $wrapZero->getElementsByTagName('dt')[0]->getElementsByTagName('a')[0];
                    if ($zero->textContent) {
                        $data[$keyZero] = [
                            'csid'       => '',
                            'dump'       => '',
                            'alias'      => '',
                            'href'       => $this->handleUrl($zero->getAttribute('href')),
                            'title'      => trim($zero->textContent) ?? '--',
                            'nest_level' => 0,
                        ];
                    }
                    foreach ($wrapZero->getElementsByTagName('dd') as $keyOne => $linkOne) {
                        if (!$linkOne->getAttribute('class')) {
                            $one = $linkOne->getElementsByTagName('a')[0];
                            if ($one->textContent) {
                                $data[$keyZero]['children'][$keyOne] = [
                                    'csid'       => '',
                                    'dump'       => '',
                                    'alias'      => '',
                                    'href'       => $this->handleUrl($one->getAttribute('href')),
                                    'title'      => trim($one->textContent) ?? '--',
                                    'nest_level' => 1,
                                ];
                            }

                            $wrapTwo = $wrapZero->getElementsByTagName('dd')[$keyOne + 1];

                            if ($wrapTwo->getAttribute('class') == 'params c-2') {
                                foreach ($wrapTwo->getElementsByTagName('strong') as $two) {
                                    $expTwo = explode('/', $two->getElementsByTagName('a')[0]->getAttribute('href'));
                                    if ($two->textContent) {
                                        $data[$keyZero]['children'][$keyOne]['children'][end($expTwo)] = [
                                            'csid'       => '',
                                            'dump'       => '',
                                            'alias'      => '',
                                            'href'       => $this->handleUrl($two->getElementsByTagName('a')[0]->getAttribute('href')),
                                            'title'      => trim($two->textContent) ?? '--',
                                            'nest_level' => 2,
                                        ];
                                    }
                                }
                                foreach ($wrapTwo->getElementsByTagName('a') as $three) {
                                    $expThree = explode('/', $three->getAttribute('href'));
                                    if ($three->textContent && isset($data[$keyZero]['children'][$keyOne]['children'][$expThree[count($expThree) - 2]])) {
                                        $data[$keyZero]['children'][$keyOne]['children'][$expThree[count($expThree) - 2]]['children'][] = [
                                            'csid'       => '',
                                            'dump'       => '',
                                            'alias'      => '',
                                            'href'       => $this->handleUrl($three->getAttribute('href')),
                                            'title'      => trim($three->textContent) ?? '--',
                                            'nest_level' => 2,
                                        ];
                                    } else {
                                        $expThree = explode('/', $three->getAttribute('href'));
                                        if ($three->textContent) {
                                            $data[$keyZero]['children'][$keyOne]['children'][end($expThree)] = [
                                                'csid'       => '',
                                                'dump'       => '',
                                                'alias'      => '',
                                                'href'       => $this->handleUrl($three->getAttribute('href')),
                                                'title'      => trim($three->textContent) ?? '--',
                                                'nest_level' => 2,
                                            ];
                                        }
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
    public function parseCategories()
    {
        $region = Region::find()
            ->join('JOIN', 'region_source rs', 'rs.region_id = region.id')
            ->where(['rs.source_id' => self::$model->id, 'region.global' => 1])->one();

        self::$region = $region ? $region->alias : '';

        $data = $this->nestCategories();
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
            $price = $node->childNodes[3]->textContent;
            $name  = $node->childNodes[1]->childNodes[1]->textContent;
            $href  = $node->childNodes[1]->childNodes[1]->getAttribute('href');

            if ($href) {
                $data[] = [
                    'name'  => $name,
                    'price' => preg_replace('/[^0-9]/', '', explode('руб.', $price)[0]),
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
                    'fullsize' => $node->getAttribute('data-url'),
                    'thumb'    => '',
                ];
            }
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

    public function buildUrl(string $regionId = '', string $categorySourceId = '', string $keyword = '')
    {
        $domain   = self::$model->domain . '/';
        $region   = Region::findOne($regionId);

        if (!$regionId) {
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
            $url = $domain . $region->alias;
        }

        if ($region && $categorySourceId && !$keyword) {
            $url = $domain . $region->alias . $category->source_url;
        }

        if ($region && !$categorySourceId && $keyword) {
            $url = $domain . $region->alias . self::ACTION_SEARCH . '&' . self::QUERY_KEYWORD . $keyword;
        }

        if ($region && $categorySourceId && $keyword) {
            $url = $domain . $region->alias . $category->source_url . self::ACTION_SEARCH . '&' . self::QUERY_KEYWORD . $keyword;
        }

        return $url;
    }

}
