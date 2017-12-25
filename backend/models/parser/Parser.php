<?php

namespace backend\models\parser;

use backend\models\parser\ParserSettler;
use backend\models\opencart\OcSettler;
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



    /**
     * @return function result (array)
     */
    public function syncProducts()
    {
        return OcSettler::saveProducts(self::$model->id);
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
            'limitPage' => $source->limit_page,
            'limitDetail' => $source->limit_detail,
            'saleFlag' => false,
            'categorySourceId',
            'categoryId',
            'keywordId',
            'regionId',
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
        self::$proxies = self::$source->proxies['ipv4'] ?? [];
    }


    /**
     * @return void
     */
    public function setAgents()
    {
        self::$agents = self::$source->headerValues['user-agent'] ?? [];
    }




    /**
     * @return void
     */
    public function setClient(string $client = null)
    {
        $constClient           = defined(self::$model->class . '::DEFINE_CLIENT') ? self::$model->class::DEFINE_CLIENT : 'curl';
        self::$client['alias'] = $client ?? $constClient;
    }




    /**
     * @return string
     */
    public function getClientAlias()
    {
        $constClient = defined(self::$model->class . '::DEFINE_CLIENT') ? self::$model->class::DEFINE_CLIENT : 'curl';
        return self::$client['alias'] ?? $constClient;
    }



    /**
     * @return int or bool
     */
    public function getMaxQuantity()
    {
        return defined(self::$model->class . '::MAX_QUANTITY') ? self::$model->class::MAX_QUANTITY : false;
    }




    /**
     * @return string
     */
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
                return;
                // throw new \Exception('Ошибка подключения клиента');
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
        $response = curl_exec($curl);
        $info = curl_getinfo($curl);
        curl_close($curl);


        if (!$response && self::$proxies) {
            $this->processResponse(1, $url);
            return $this->curlSession($url);
        }
        // if ($info['http_code'] == 403) {
            // if (self::$agents) {
            if (!$response && self::$agents) {
                $this->processResponse(2, $url);
                return $this->curlSession($url);
            } else {
                // throw new \Exception('Source returned error 403 (Access denied) - скорее всего не хватает нужного user-agent заголовка.');
            }
        // }

        if ($response) {
            // $captcha = $this->getNodes($response, '//*[contains(@*, \'captcha\')]');
            // if ($captcha && $captcha->length) {
            //     $this->processResponse(1, $url);
            //     $this->processResponse(2, $url);
            //     return $this->curlSession($url);
            // }
            // else {
                $this->processResponse(0, $url);
                return $response;
            // }
        } else {
            return;
            // throw new \Exception('HTTP Code ' . $info['http_code'] . '. Either url or Curl options are bad. URL: ' . $url);
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
        // $phantom->getEngine()->debug(true);
        $phantom->getEngine()->setPath(Yii::getAlias('@phantom'));
        $phantom->getEngine()->addOption('--ignore-ssl-errors=true');
        $phantom->getEngine()->addOption('--load-images=false');

        $request  = $phantom->getMessageFactory()->createRequest($url);
        // $request->setDelay(0);
        // $request->setTimeout(0);

        $response = $phantom->getMessageFactory()->createResponse();

        $phantom->send($request, $response);

        $this->processResponse(0, $url);
        
        if (!$response->getContent() && self::$proxies) {
            // $this->processResponse(1, $url);
            // return $this->phantomSession($url);
        }

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
        } 

        elseif ($type == 'catalog'
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

            // SALE: Sale flag
            $xpathSale = '';
            if (self::$model->saleFlag === true) {
                if (method_exists(self::$model->class, 'xpathSale')) {
                    $xpathSale = self::$model->class::xpathSale($xpath);
                } 
                // else {
                //     return;
                // }
            }

            if ($response) {
                $nodes = $this->getNodes($response, $xpath, $xpathSale);
                if (isset($nodes->length) && $nodes->length) {
                    return $this->getProducts($nodes);
                }
                elseif ($nodes === true) {
                    return [[]];
                }
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

                if ($type == 'details') {
                    $data['description'] = $this->getDescriptionData($dataObject);
                    $data['attribute'] = $this->getAttributeData($dataObject);
                    $data['image'] = $this->getImageData($dataObject);
                    return $data;
                }

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
                        $priceNodes = $this->getNodes($response, self::$model->class::XPATH_PRICE);
                        $data['price'] = $this->getPriceData($priceNodes);
                    }

                    return $data;

                } 
                elseif ($type == 'description'
                    && defined(self::$model->class . '::XPATH_DESCRIPTION')
                    && self::$model->class::XPATH_DESCRIPTION) {
                    
                    $nodes = $this->getNodes($response, self::$model->class::XPATH_DESCRIPTION);
                    return $this->getDescriptionData($nodes);

                } 
                elseif ($type == 'attribute'
                    && defined(self::$model->class . '::XPATH_ATTRIBUTE')
                    && self::$model->class::XPATH_ATTRIBUTE) {

                    $nodes = $this->getNodes($response, self::$model->class::XPATH_ATTRIBUTE);
                    return $this->getAttributeData($nodes);

                } 
                elseif ($type == 'image'
                    && defined(self::$model->class . '::XPATH_IMAGE')
                    && self::$model->class::XPATH_IMAGE) {

                    $nodes = $this->getNodes($response, self::$model->class::XPATH_IMAGE);
                    return $this->getImageData($nodes);

                } 
                elseif ($type == 'price'
                    && defined(self::$model->class . '::XPATH_PRICE')
                    && self::$model->class::XPATH_PRICE) {

                    $nodes = $this->getNodes($response, self::$model->class::XPATH_PRICE);
                    return $this->getPriceData($nodes);

                }
            }
        }
    }






    /**
     * @return object, instance of DOMXpath 
     */
    // public function getNodes(string $response, string $xpathQuery)
    public function getNodes(string $response, string $xpathQuery, string $xpathSale = '')
    {
        $dom = new \DOMDocument();
        $dom->formatOutput = true;
        $dom->preserveWhiteSpace = false;
        @$dom->loadHTML($response);

        $xpath = new \DOMXPath($dom);
        $nodes = $xpath->query($xpathQuery);

        // return $nodes;
        if (!$xpathSale) {
            return $nodes;
        }
        else {
            $sales = $xpath->query($xpathSale);
            if ($sales->length) {
                return $sales;
            }
            elseif ($nodes->length) {
                return true;
            }
        }
    }





    /**
     * @return string
     */
    public function processUrl(string $url, int $sourceId = null)
    {   
        if (self::$model && self::$model->domain && !$sourceId) {
            $domain = self::$model->domain;
        } 
        elseif ($sourceId) {
            $domain = Source::findOne($sourceId)->source_url;
        }

        if (strpos($domain, 'www') === false) {
            $protocol = explode('//', $domain)[0] . '//';
            $domain = explode('//', $domain)[1];
        } else {
            $protocol = explode('www.', $domain)[0] . 'www.';
            $domain = explode('www.', $domain)[1];
        }

        if (strpos($url, $domain) === false) {
            $url = $protocol . trim($domain, '/') . $url;
        }

        return $url;
    }





    /**
     * @return int
     */
    public function parseProducts()
    {
        $time = microtime(true);

        $settler = new ParserSettler();

        $dataWarnings = [];
        $dataProducts = [];
        $dataDetails  = [];

        // $warnings = $this->parse('warning', self::$model->url);

        // if (!$warnings) {

            $page = 0;

            $excuse = defined(self::$model->class . '::PAGER_EXCUSE') && self::$model->class::PAGER_EXCUSE;
            // $condition = ($last === end($data) && !$excuse) || $page == 4;
            // $condition = $last === end($data) || $page > self::$model->limitPage;

            // PAGER: If method defined, get and stop at last page.
            // TODO: Rewrite (move to parse() method or elsewhere) - not to make excessive requests.
            if (defined(self::$model->class . '::XPATH_PAGER') && self::$model->class::XPATH_PAGER) {
                if (method_exists(self::$model->class, 'lastPage')) {
                    $lastPage = self::$model->class::lastPage($this->getNodes($this->curlSession(self::$model->url), self::$model->class::XPATH_PAGER));
                }
            }


            while ($data = $this->parse('catalog', self::$model->url, $this->pageQuery($page, self::$model->url))) {
                $page++;
                
                // LAST PRODUCT: Last product of every next page. When source returns non-empty results, even if last page is passed over.
                if (end($dataProducts) === end($data)) {
                    if (!$excuse) {
                        break;
                    }
                }

                else {
                    foreach ($data as $product) {
                        if ($product) {
                            $dataProducts[] = $product;
                        }
                    }
                }

                // LAST PAGE
                if (isset($lastPage) && $page === $lastPage) {
                // if ($page == 1) {
                    break;
                }
            }

            $productUrls = $settler->saveProducts($dataProducts, self::$model);

        // } else {

        //     foreach ($warnings as $warning) {
        //         $dataWarnings[] = $warning;
        //     }

        // }

        self::$model->warnings = $dataWarnings;
        self::$model->products = $dataProducts;
        self::$model->details  = $productUrls;            

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

        foreach ($details as $id => $url) {
            $detailsData = $this->parse('details', $url);

            if (isset($detailsData['description']) && count($detailsData['description'])) {
                $settler->saveDescriptions($detailsData['description'], (int)$id);
            }
            if (isset($detailsData['attribute']) && count($detailsData['attribute'])) {
                $settler->saveAttributes($detailsData['attribute'], (int)$id);
            }
            if (isset($detailsData['image']) && count($detailsData['image'])) {
                $settler->saveImages($detailsData['image'], (int)$id);
            }

            $detailsCount++;

            // if ($detailsCount % 100 == 0 || $url == end($details)) {
                print_r($detailsCount . ' > ');
            // }

            // if ($detailsCount == 2) {
            //     break;
            // }
        }

        return $detailsCount;
    }

}
