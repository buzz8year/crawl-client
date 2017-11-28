<?php

namespace backend\models\parser\source;

use backend\models\parser\ParserSourceInterface;
use backend\models\parser\Parser;
use backend\models\CategorySource;
use backend\models\Region;
use backend\models\Source;

class AvitoParser extends Parser implements ParserSourceInterface
{
    const ACTION_SEARCH  = '?s=101';
    const QUERY_CATEGORY = '';
    const QUERY_KEYWORD  = 'q=';

    const XPATH_WARNING = ''; // At Catalog/Search Page
    const XPATH_CATALOG = '//*[contains(@class, \'item_table-header\')]'; // At Catalog/Search Page

    const XPATH_SUPER       = ''; // At Product Page. JS Script with JSON Whole Data Object
    const XPATH_ATTRIBUTE   = ''; // At Product Page
    const XPATH_DESCRIPTION = '//*[contains(@class, \'item-description-text\')]'; // At Product Page
    const XPATH_IMAGE       = '//*[contains(@class, \'gallery-extended-img-frame\')]'; // At Product Page. Full size.

    const LEVEL_ONE_CATEGORY_NODE  = '//*[contains(@class, \'b-category-list\')]//a'; // At HomePage navmenu
    const LEVEL_ONE_CATEGORY_CLASS = 'root-category'; // At Level One Category Page leftmenu
    const CATEGORY_WRAP_NODE       = '//*[contains(@class, \'catalog-counts\')]/*[contains(@class, \'catalog-counts__section\')]'; // At HomePage navmenu

    const CURL_FOLLOW = 1; // CURLOPT_FOLLOWLOCATION


    static $region;

    /**
     * @return array
     */
    public function nestCategories(string $parentUrl = '', string $parentTitle = '', int $nestLevel = -1)
    {
        $data = [];

        if ($responseCategory = $this->curlSession(self::$model->domain . '/' . self::$region . $parentUrl)) {
            if (($wrappers = $this->getNodes($responseCategory, self::CATEGORY_WRAP_NODE)) && $wrappers->length == 2) {
                $categoryWrapper = $wrappers[0];
            }
        }

        if (isset($categoryWrapper)) {

            if (($findScript = $categoryWrapper->getElementsByTagName('script')) && $findScript->length) {
                $script = $findScript[0];
            }

            if (isset($script)) {
                $left       = explode('[{', $script->nodeValue)[1];
                $right      = explode('}]', $left)[0];
                $categories = json_decode('[{' . $right . '}]');
            } else {
                $categories = $categoryWrapper->getElementsByTagName('a');
            }

            if (isset($categories)) {
                $nestLevel++;

                foreach ($categories as $key => $category) {
                    if ($key > 0) {
                        $title = isset($script) ? $category->name : $category->textContent;
                        $deque = isset($script) ? explode('?', $category->url)[0] : explode('?', $category->getAttribute('href'))[0];

                        $dereg = explode('/', $deque)[1];
                        $href  = explode('/' . $dereg, $deque)[1];
                        $alias = ltrim($href, '/');

                        // $thisTitle = $parentTitle . ($nestLevel ? ' > ' : '') . $title;

                        $data[$alias] = [
                            'csid'       => '',
                            'dump'       => '',
                            'href'       => $href,
                            'alias'      => $alias,
                            'title'      => $title,
                            'nest_level' => $nestLevel,
                            'children'   => $this->nestCategories($href, $title, $nestLevel),
                        ];
                    }

                    if ($key == 2 || $nestLevel == 4) {
                        // Do not forget to REMOVE
                        break;
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
    public function getProducts(\DOMNodeList $nodes)
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

    public function urlBuild(string $regionId = '', string $categorySourceId = '', string $keyword = '')
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
