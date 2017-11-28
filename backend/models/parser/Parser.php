<?php

namespace backend\models\parser;

use backend\models\parser\ParserSettler;
use backend\models\Source;
use JonnyW\PhantomJs\Client as Phantom;
use Yii;
use yii\base\DynamicModel;

/**
 * Parser prepares the ground for & implements parsing works feeding a specific parser class related to the source.
 */
class Parser implements ParserInterface
{
    static $model;
    static $source;

    static $agents;
    static $proxies;
    static $options;

    static $client;

    public function parseProducts()
    {
        $time = microtime(true);

        $settler = new ParserSettler();

        $dataWarnings = [];
        $dataProducts = [];
        $dataDetails  = [];

        $warnings = $this->parse('warning', self::$model->url);

        if (!$warnings) {

            $page = 0;

            while ($data = $this->parse('catalog', self::$model->url, $this->pageQuery($page, self::$model->url))) {
                $last = end($dataProducts);
                $page++;

                // if ($last === end($data) || $page > self::$model->limitPage) {
                if ($last === end($data) || $page > 3) {
                    break;
                } else {
                    foreach ($data as $product) {
                        $dataProducts[] = $product;
                    }
                }
            }

            $productUrls = $settler->saveProducts($dataProducts, self::$model);

        } else {

            foreach ($warnings as $warning) {
                $dataWarnings[] = $warning;
            }

        }

        self::$model->warnings = $dataWarnings;
        self::$model->products = $dataProducts;
        self::$model->details  = $productUrls;            
        echo('details' . count(self::$model->details));

        $settler->logResults(self::$model, microtime(true) - $time);

        if ($dataProducts) {
            return count($dataProducts);
        }
    }

    /**
     * parseDetails() parses products' details
     * @param array $products - products array with id-url pairs
     * @return void
     * TODO: integrate differentiated methods of parsing desc, attr, img to a single one
     */
    public function parseDetails(array $urlProducts = [])
    {
        $settler = new ParserSettler();

        $detailsCount = 0;


        $details = $urlProducts ? $urlProducts : self::$model->details;
        echo('details' . count($details));

        foreach ($details as $id => $url) {
            $detailsData = $this->parse('details', $url);

            if (isset($detailsData['description']) && count($detailsData['description'])) {
                $settler->saveDescriptions($detailsData['description'], $id);
            }
            if (isset($detailsData['attribute']) && count($detailsData['attribute'])) {
                $settler->saveAttributes($detailsData['attribute'], $id);
            }
            if (isset($detailsData['image']) && count($detailsData['image'])) {
                $settler->saveImages($detailsData['image'], $id);
            }

            $detailsCount++;
        }

        return $detailsCount;
    }

    /**
     * @return object
     */
    public function createModel(int $sourceId)
    {
        $source = Source::findOne($sourceId);

        $model = new DynamicModel([
            'id'     => $source->id,
            'title'  => $source->title,
            'domain' => $source->source_url,
            'class'  => $source->class_namespace,
            'limitPage'  => $source->limit_page,
            'limitDetail'  => $source->limit_detail,
            'regionId',
            'categorySourceId',
            'categoryId',
            'keywordId',
            'warnings',
            'products',
            'details',
            'url',
        ]);

        $model->addRule(['class'], 'required');

        self::$model  = $model;
        self::$source = $source;

        $this->setProxies();
        $this->setAgents();
        $this->setOptions();
        $this->setClient();

        return $model;
    }

    /***/
    public function sessionClient(string $url)
    {
        switch (self::$client['alias']) {
            case 'curl':
                return $this->curlSession($url);

            case 'phantom':
                $response = $this->phantomSession($url);
                // if ($response) {
                    return $response;
                // } else {
                    // throw new \Exception(self::$client['alias'] . ' Client session response is bad.');
                // }

            default:
                throw new \Exception('Ошибка подключения клиента');
        }
    }

    /**
     * curlSession() function utilizes self::$options, and establishes a curl session based on them.
     * Conditionaly recursive.
     * Recursion condition is based on whether "proxy" used is successful or not.
     * If current proxy is failure, self:$proxies array is shifted, and self::$options array is recreated.
     * If all proxies in array (self::$proxies) are failure, then session goes finally proxy-less.
     * Proxy fails are written to history.
     *
     * @param string $url
     * @return array
     */
    public function curlSession(string $url)
    {
        if (!$curl = curl_init($url)) {
            throw new \Exception('Curl library problem.');
        }
        if (!$copt = curl_setopt_array($curl, self::$options)) {
            throw new \Exception('Setting Curl options was not successful.');
        }
        $data = curl_exec($curl);
        $info = curl_getinfo($curl);
        curl_close($curl);

        if (!$data && self::$proxies) {
            $this->processResponse(1, $url);
            return $this->curlSession($url);
        }
        if ($info['http_code'] == 403) {
            if (self::$agents) {
                $this->processResponse(2, $url);
                return $this->curlSession($url);
            } else {
                throw new \Exception('Source returned error 403 (Access denied) - скорее всего не хватает нужного user-agent заголовка.');
            }
        }

        if ($data) {
            // print_r($info);
            // print_r($data);
            $this->processResponse(0, $url);
            return $data;
        } else {
            throw new \Exception('HTTP Code ' . $info['http_code'] . '. Either url or Curl options are bad. URL: ' . $url);
        }
    }

    /**
     * phantomSession() function utilizes self::$options, and establishes a curl session based on them.
     *
     * @param string $url
     * @return array
     */
    public function phantomSession(string $url)
    {
        $phantom = Phantom::getInstance();
        $phantom->getEngine()->setPath(Yii::getAlias('@phantom'));

        $request  = $phantom->getMessageFactory()->createRequest($url);
        $response = $phantom->getMessageFactory()->createResponse();

        $phantom->send($request, $response);

        $this->processResponse(0, $url);

        return $response->getContent();
    }

    /**
     * processFail() logs fails to history, shifts agent and proxy arrays and resets curl options
     *
     * @param string $type
     * @return void
     */
    public function processResponse(int $status, string $url)
    {
        $proxy = self::$proxies[0] ?? [];
        $agent = self::$agents[0] ?? [];

        ParserSettler::logSession($status, $url, self::$model->id, $proxy, $agent, $this->getClientAlias());

        switch ($status) {
            case 0:
                break;
            case 1:
                array_shift(self::$proxies);
                break;
            case 2:
                array_shift(self::$agents);
                break;
        }

        $this->setOptions();
    }

    /**
     * parse() function determines the "type" of parsing needed, and further related processing, recieved from curl request.
     * IMPORTANT: curlSession() needs to be called separately for every "type" condition, since we need to recreate the "options" for the session,
     * and if it would be called only once, above all conditions, we would not be able to determine if "options" are legitimate or not.
     *
     * @param string $type
     * @param string $url
     * @param string $page, default empty string
     * @return array
     */
    public function parse(string $type, string $url, string $page = '')
    {
        if ($type == 'warning'
            && defined(self::$model->class . '::XPATH_WARNING')
            && self::$model->class::XPATH_WARNING) {

            $response = $this->sessionClient($url);
            $nodes    = $this->getNodes($response, self::$model->class::XPATH_WARNING);
            if ($nodes->length) {
                return $this->getWarningData($nodes);
            }

        } elseif ($type == 'catalog'
            && defined(self::$model->class . '::XPATH_CATALOG')
            && self::$model->class::XPATH_CATALOG) {

            // When pagination string is somewhere in between of the url, following becomes usefull
            $pageUrl = defined(self::$model->class . '::PAGER_SPLIT_URL')
                && self::$model->class::PAGER_SPLIT_URL
                ? $page
                : ($url . $page);

            $response = $this->sessionClient($pageUrl);
            // print_r($response);

            // When Search page & Catalog page templates are different, following becomes usefull
            $xpath = self::$model->class::XPATH_CATALOG;

            if (isset(self::$model->class::$template) && self::$model->class::$template == 'search') {
                if (defined(self::$model->class . '::XPATH_SEARCH') && self::$model->class::XPATH_SEARCH) {
                    $xpath = self::$model->class::XPATH_SEARCH;
                }
            }

            $nodes = $this->getNodes($response, $xpath);
            if ($nodes->length) {
                return $this->getProducts($nodes);
            } 
            // else {
            //     throw new \Exception(
            //         'Your parsing XPATH string does not match any elements: ' . 
            //         PHP_EOL . $xpath . PHP_EOL . PHP_EOL .
            //         'Also check requested URL: ' .
            //         PHP_EOL . $pageUrl
            //     );
            // }

        } else {

            if (defined(self::$model->class . '::XPATH_SUPER') && self::$model->class::XPATH_SUPER) {

                $response = $this->sessionClient($url);
                if ($object = $this->getNodes($response, self::$model->class::XPATH_SUPER)) {
                    if ($object->length) {
                        $dataObject = $this->getSuperData($object);
                    }
                }
            }

            if (isset($dataObject)) {

                if ($type == 'description') {
                    return $this->getDescriptionData($dataObject);
                } elseif ($type == 'attribute') {
                    return $this->getAttributeData($dataObject);
                } elseif ($type == 'image') {
                    return $this->getImageData($dataObject);
                } elseif ($type == 'price') {
                    return $this->getPriceData($dataObject);
                }

            } else {

                $response = $this->sessionClient($url);

                if ($type == 'details') {

                    $data = [];

                    if (defined(self::$model->class . '::XPATH_DESCRIPTION') && self::$model->class::XPATH_DESCRIPTION) {
                        $descNodes = $this->getNodes($response, self::$model->class::XPATH_DESCRIPTION);
                        $data['description'] = $this->getDescriptionData($descNodes);
                    }
                    if (defined(self::$model->class . '::XPATH_ATTRIBUTE') && self::$model->class::XPATH_ATTRIBUTE) {
                        $attrNodes = $this->getNodes($response, self::$model->class::XPATH_ATTRIBUTE);
                        $data['attribute'] = $this->getAttributeData($attrNodes);
                    }
                    if (defined(self::$model->class . '::XPATH_IMAGE') && self::$model->class::XPATH_IMAGE) {
                        $imgNodes = $this->getNodes($response, self::$model->class::XPATH_IMAGE);
                        $data['image'] = $this->getImageData($imgNodes);
                    }
                    if (defined(self::$model->class . '::XPATH_PRICE') && self::$model->class::XPATH_PRICE) {
                        $pricePodes = $this->getNodes($response, self::$model->class::XPATH_PRICE);
                        $data['price'] = $this->getPriceData($pricePodes);
                    }

                    return $data;

                } 
                elseif ($type == 'description'
                    && defined(self::$model->class . '::XPATH_DESCRIPTION')
                    && self::$model->class::XPATH_DESCRIPTION) {
                    
                    $nodes    = $this->getNodes($response, self::$model->class::XPATH_DESCRIPTION);
                    return $this->getDescriptionData($nodes);

                } 
                elseif ($type == 'attribute'
                    && defined(self::$model->class . '::XPATH_ATTRIBUTE')
                    && self::$model->class::XPATH_ATTRIBUTE) {

                    $nodes    = $this->getNodes($response, self::$model->class::XPATH_ATTRIBUTE);
                    return $this->getAttributeData($nodes);

                } 
                elseif ($type == 'image'
                    && defined(self::$model->class . '::XPATH_IMAGE')
                    && self::$model->class::XPATH_IMAGE) {

                    $nodes    = $this->getNodes($response, self::$model->class::XPATH_IMAGE);
                    return $this->getImageData($nodes);

                } 
                elseif ($type == 'price'
                    && defined(self::$model->class . '::XPATH_PRICE')
                    && self::$model->class::XPATH_PRICE) {

                    $nodes    = $this->getNodes($response, self::$model->class::XPATH_PRICE);
                    return $this->getPriceData($nodes);

                }
            }
        }
    }

    /**
     * @return object, instance of DOMXpath 
     */
    public function getNodes(string $response, string $xpathQuery)
    {
        $dom                     = new \DOMDocument();
        $dom->formatOutput       = true;
        $dom->preserveWhiteSpace = false;
        @$dom->loadHTML($response);

        $xpath = new \DOMXPath($dom);
        $nodes = $xpath->query($xpathQuery);

        return $nodes;
    }

    /**
     * @return string
     */
    public function processUrl(string $url)
    {   
        $trimDomain = self::$model->domain;
        if (strpos(self::$model->domain, 'www') === false) {
            $trimDomain = explode('//', self::$model->domain)[1];
        } else {
            $trimDomain = explode('www.', self::$model->domain)[1];
        }
        if (strpos($url, $trimDomain) === false) {
            $url = self::$model->domain . $url;
        }
        return $url;
    }

    /**
     * @return void
     */
    public function setOptions()
    {
        $random  = self::$agents ? rand(0, count(self::$agents) - 1) : null;
        $agent   = self::$agents[$random] ?? [];
        $options = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => self::$model->class::CURL_FOLLOW,
            CURLOPT_PROXY          => self::$proxies[0]['address'] ?? null,
            CURLOPT_PROXYUSERPWD   => self::$proxies[0]['password'] ?? null,
            CURLOPT_HTTPHEADER     => self::$agents[0] ?? [],
        ];

        self::$options = $options;
    }

    /**
     * @return void
     */
    public function setProxies()
    {
        self::$proxies = self::$source->getProxies()['ipv4'] ?? [];
    }

    /**
     * @return void
     */
    public function setAgents()
    {
        self::$agents = self::$source->getHeaderValues()['user-agent'] ?? [];
    }

    /**
     * @return void
     */
    public function setClient(string $client = null)
    {
        $constClient           = defined(self::$model->class . '::DEFINE_CLIENT') ? self::$model->class::DEFINE_CLIENT : 'curl';
        self::$client['alias'] = $client ?? $constClient;
    }

    /***/
    public function getClientAlias()
    {
        $constClient = defined(self::$model->class . '::DEFINE_CLIENT') ? self::$model->class::DEFINE_CLIENT : 'curl';
        return self::$client['alias'] ?? $constClient;
    }

    public function getMaxQuantity()
    {
        return defined(self::$model->class . '::MAX_QUANTITY') ? self::$model->class::MAX_QUANTITY : false;
    }

}
