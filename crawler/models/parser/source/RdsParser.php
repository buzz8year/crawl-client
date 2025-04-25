<?php

namespace crawler\models\parser\source;

use ArrayObject;
use crawler\models\parser\ParserSourceInterface;
use crawler\models\parser\ParserProvisioner;
use crawler\models\parser\Parser;
use crawler\models\category\CategorySource;
use crawler\models\region\Region;
use crawler\models\source\Source;
use Yii;


class RdsParser extends Parser implements ParserSourceInterface
{
    public const ACTION_SEARCH  = 'search/';
    public const QUERY_CATEGORY = '';
    public const QUERY_KEYWORD  = 'q=';

    public const XPATH_WARNING = ''; // At Catalog/Search Page
    public const XPATH_CATALOG = '//div[@class=\'catalog-elements quoteList\']//div[@class=\'element unit quoteItem\']'; // At Catalog/Search Page

    public const XPATH_SUPER       = ''; // At Product Page. JS Script with JSON Whole Data Object
    public const XPATH_ATTRIBUTE   = '//table[contains(@class, \'table-property\')]'; // At Product Page
    public const XPATH_DESCRIPTION = '//div[contains(@class, \'description\')]'; // At Product Page
    public const XPATH_IMAGE       = '//a[contains(@itemprop=\'image\']'; // At Product Page. Full size.

    // public const CATEGORY_NODE  = ''; // At HomePage navmenu
    public const CATEGORY_WRAP_NODE  = '//div[@class=\'quoteList\']'; // At HomePage navmenu
    // public const CATEGORY_WRAP_CLASS = 'catalog-subcatalog'; // At Level One Category Page leftmenu

    public const CURL_FOLLOW = 1; // CURLOPT_FOLLOWLOCATION

    public const MAX_QUANTITY = '';

    public const DEFINE_CLIENT = 'curl'; // CURLOPT_FOLLOWLOCATION

    public static $region;

    /**
     * @return array
     */
    public function parseCategories()
    {
        $data = [];
        $dataTwo = [];

        $response = $this->strategy->setSessionClient($this->factory->model->domain . '/catalog/');
        if ($response) 
        {
            $nodes = $this->domService->getNodes($response, self::CATEGORY_WRAP_NODE);
            if ($nodes && $nodes->length) 
            {
                foreach ($nodes as $keyZero => $node) 
                {
                    $zero = $node->getElementsByTagName('a')[0];
                    if (($zeroClass = $zero->parentNode->getAttribute('class')) && $zeroClass == 'section-header') 
                    {
                        $data[$keyZero] = [
                            'csid'       => '',
                            'dump'       => '',
                            'alias'      => '',
                            'href'       => $zero->getAttribute('href'),
                            'title'      => trim($zero->textContent),
                            'nest_level' => 0,
                        ];
                    }

                    foreach ($node->getElementsByTagName('li') as $keyOne => $child) 
                    {
                        if (($checkClass = $child->parentNode->parentNode->getAttribute('class')) && strpos($checkClass, 'cols') !== false) 
                        {
                            $one = $child->getElementsByTagName('a')[0];
                            if ($one->parentNode === $child) 
                            {
                                $data[$keyZero]['children'][$keyOne] = [
                                    'csid'       => '',
                                    'dump'       => '',
                                    'alias'      => '',
                                    'href'       => $one->getAttribute('href'),
                                    'title'      => trim($one->textContent),
                                    'nest_level' => 1,
                                ];
                            }

                            foreach ($child->getElementsByTagName('a') as $keyTwo => $grand) 
                            {
                                if ($keyTwo > 0) 
                                {
                                    $exp = explode('catalog/', $grand->getAttribute('href'));

                                    if (isset($exp[1])) 
                                    {
                                        $alias = $exp[1];
                                        $data[$keyZero]['children'][$keyOne]['children'][] = [
                                            'csid'       => '',
                                            'dump'       => '',
                                            'alias'      => preg_match('/[a-z]/i', $alias) ? trim($alias, '/') : '',
                                            'href'       => $grand->getAttribute('href'),
                                            'title'      => explode(' (', trim($grand->textContent))[0],
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
        
        return $data;
    }



    /**
     * @return array
     */
    public function getWarningData(\DOMNodeList $nodes)
    {
        return [];
    }




    /**
     * @return
     */
    public static function xpathSale(string $xpath)
    {
        $extend = ' and .//div[contains(@class, \'new-item-list-discount\')]';
        $explode  = rtrim($xpath, ']');
        $xpath = $explode . $extend . ']';

        return $xpath;
    }




    /**
     * Extracting data from the product item's element of a category/search page
     * @return array
     */
    public function getProducts($nodes)
    {
        $data = [];
        foreach ($nodes as $node) 
        {
            $price = 0;
            $title = 'not-found';
            $href = 'not-found';
            
            foreach ($node->getElementsByTagName('*') as $child) 
            {
                if (strpos($child->getAttribute('itemprop'), 'name') !== false)
                {
                    $title = $child->textContent;   
                    $href = $child->getAttribute('href');
                }
                // if (strpos($child->getAttribute('class'), 'new-item-list-name') !== false)
                //     $title = $child->getElementsByTagName('*')[0];

                if (strpos($child->getAttribute('class'), 'price_span') !== false)
                    $price = preg_replace('/[^0-9]/', '', $child->textContent);
            }
            $data[] = [
                'price' => $price,
                'name'  => $title,
                'href'  => $href,
            ];
        }

        // Yii::$app->session->addFlash('warning', json_encode($data));

        return $data;
    }

    /**
     * Extracting an object (of all the data needed) from the <script/> element
     * @return object
     */
    public function getSuperData(\DOMNodeList $nodes): array {
        return [];
    }

    /**
     * Getting descriptions data from the object produced by getSuperData()
     * @return array
     */
    public function getDescriptionData($object)
    {
        $data = [];
        foreach ($object as $node) 
        {
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
        foreach ($object as $node) 
        {
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
        foreach ($object as $node) 
        {
            $data[] = [
                'fullsize' => $node->getAttribute('href'),
                'thumb' => $node->getElementsByTagName('img')[0]->getAttribute('src'),
            ];
        }
        return $data;
    }

    public function pageQuery(int $page, string $url)
    {
        $pageQuery = strpos($url, '?') !== false 
            ? '&PAGEN_1='
            : '?PAGEN_1=';

        $returnPage = $pageQuery . $page;

        return $returnPage;
    }

    public function urlBuild(string $regionSourceId = '', string $categorySourceId = '', string $keyword = '')
    {
        $domain   = $this->factory->model->domain;
        $category = CategorySource::findOne($categorySourceId);

        $keyword = urlencode(iconv('utf-8', 'windows-1251//IGNORE', str_replace(' ', '+', $keyword)));

        $url = '';

        if ($categorySourceId && !$keyword) {
            $url = $this->processUrl($category->source_url);
        }

        if ($categorySourceId && $keyword && $category->source_category_alias) {
            $url = $domain . '/' . self::ACTION_SEARCH . $category->source_category_alias . '/?' . self::QUERY_KEYWORD . $keyword;
        }

        if (!$categorySourceId && $keyword) {
            $url = $domain . '/' . self::ACTION_SEARCH . '?' . self::QUERY_KEYWORD . $keyword;
        }

        return $url;
    }

}
