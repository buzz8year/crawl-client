<?php

namespace crawler\models\parser;

use crawler\models\parser\ParserSettler;

/**
 * Parser prepares the ground for & implements parsing works feeding a specific parser class related to the source.
 */
class Parser implements ParserInterface
{
    public static $source;
    public ParserFactory $factory;
    public ParserStrategy $strategy;
    public ParserDOMService $domService;
    public ParserSettler $settler;

    public function __construct(ParserFactory $factory)
    {
        $this->factory = $factory;
        $this->settler = new ParserSettler($this->factory);
        $this->strategy = new ParserStrategy($this->factory);
        $this->domService = new ParserDOMService($this->factory);
    }

    public function processUrl(string $url)
    {   
        return $this->factory->source->appendUrl($url);
    }

    public function isConstantDefined(string $constant): bool
    {
        return defined($this->factory->model->class . '::' . $constant) 
            && $this->factory->model->class::{$constant};
    }

    public function isTypeWarning(string $type): bool
    {
        return $type == 'warning' 
            && $this->isConstantDefined('XPATH_WARNING');
    }

    public function isTypeCatalog(string $type): bool
    {
        return $type === 'catalog' 
            && $this->isConstantDefined('XPATH_CATALOG');
    }

    public function log(float $time)
    {
        $this->settler->logResults($this->factory->model, microtime(true) - $time);
    }

    // public function parseSuper($type, $response) {
    //     if ($this->isConstantDefined('XPATH_SUPER')) {
    //         $nodes = $this->domService->getNodes($response, $this->factory->model->class::XPATH_SUPER);
    //         if ($nodes && $nodes->length)
    //             $superObject = $this->getSuperData($nodes);
    //     }
    //     if (isset($superObject)) {
    //         if ($type == 'details') {
    //             $data['description'] = $this->domService->getDataByType('description', $superObject);
    //             $data['attribute'] = $this->domService->getDataByType('attribute', $superObject);
    //             $data['image'] = $this->domService->getDataByType('image', $superObject);
    //             return $data;
    //         }
    //         return $this->domService->getDataByType($type, $superObject);
    //     } 
    // }
    
    /**
     * parseDetails() parses products' details
     * @param array $products - products array with id-url pairs
     * TODO: integrate differentiated methods of parsing desc, attr, img to a single one
     */
    public function parseDetails(array $urlProducts = [])
    {
        $details = $urlProducts ?? $this->factory->model->details;
        if (empty($details)) 
            return 0;

        foreach ($details as $id => $url) 
        {
            $detailsData = $this->parse('details', $url);

            if (!empty($detailsData['description']))
                $this->settler->saveDescriptions($detailsData['description'], (int)$id);

            if (!empty($detailsData['attribute']))
                $this->settler->saveAttributes($detailsData['attribute'], (int)$id);

            if (!empty($detailsData['image']))
                $this->settler->saveImages($detailsData['image'], (int)$id);

            $detailsCount++;
        }
        return $detailsCount;
    }


    public function parseCatalog(string $url, string $page = ''): array
    {
        // NOTE: When pagination string is somewhere in between of the url, following becomes usefull
        $pageUrl = !$this->isConstantDefined('PAGER_SPLIT_URL')
            ? sprintf('%s%s', $url, $page)
            : $page;

        $response = $this->factory->getClientAlias() != 'file' 
            ? $this->strategy->setSessionClient($pageUrl)
            : $this->strategy->zipSession();

        // NOTE: When Search page & Catalog page templates are different, following becomes usefull
        $xpath = $this->factory->model->class::XPATH_CATALOG;

        if (isset($this->factory->model->class::$template) 
            && $this->factory->model->class::$template == 'search' 
            && $this->isConstantDefined('XPATH_SEARCH'))
                $xpath = $this->factory->model->class::XPATH_SEARCH;

        // NOTE: Sale flag
        $xpathSale = '';
        if ($this->factory->model->saleFlag === true && method_exists($this->factory->model->class, 'xpathSale'))
            $xpathSale = $this->factory->model->class::xpathSale($xpath);
            
        if ($response) 
        {
            if ($this->factory->getClientAlias() == 'file') 
                return $this->getProducts($this->domService->getPlainXml($response));

            $nodes = $this->domService->getNodes($response, $xpath, $xpathSale);
            if (isset($nodes->length) && $nodes->length)
                return $this->getProducts($nodes);
        }
        return [[]];
    }

    public function parseWarnings(string $url)
    {
        $response = $this->strategy->setSessionClient($url);
        $nodes = $this->domService->getNodes($response, $this->factory->model->class::XPATH_WARNING);

        if ($nodes->length)
            return $this->getWarningData($nodes);
    }

    /**
     * parse() function determines the "type" of parsing needed, and further related processing, recieved from curl request.
     * IMPORTANT: curlSession() needs to be called separately for every "type" condition, since we need to recreate the "options" for the session,
     * and if it would be called only once, above all conditions, we would not be able to determine if "options" are legitimate or not.
     *
     * @param string $type
     * @param string $url
     * @param string $page, default empty string
     */
    public function parse(string $type, string $url, string $page = '')
    {
        if ($this->isTypeWarning($type)) 
            return $this->parseWarnings($url);

        elseif ($this->isTypeCatalog($type))
            return $this->parseCatalog($url, $page);

            
        $response = $this->strategy->setSessionClient($url);
        if (empty($response))
            return null;
        // if ($this->isConstantDefined('XPATH_SUPER')) 
        //     return $this->parseSuper($type, $response);
        if ($type == 'details') 
            return $this->domService->getDetailNodes($response);

        return $this->domService->getDataByType($type, $response);
    }

    public function run()
    {
        $time = microtime(true);

        $dataWarnings = [];
        $dataProducts = [];

        // PAGER: If method defined, get and stop at last page.
        // NOTE: Rewrite (move to parse() method or elsewhere) - not to make excessive requests.
        if ($this->isConstantDefined('XPATH_PAGER') 
            && method_exists($this->factory->model->class, 'lastPage'))
            $lastPage = $this->factory->model->class::lastPage($this->domService->getNodes($this->strategy->curlSession(), $this->factory->model->class::XPATH_PAGER));
        
        $url = $this->factory->model->url;
        $page = 0;

        // NOTE: Main loop. All parsing happens here. 
        // WARNING: Watch the base/break cases! Otherwise, loops infinitely.
        while ($data = $this->parse('catalog', $url, $this->pageQuery(++$page, $url))) 
        {
            // NOTE: If limit setting exceeded.
            if ($page > $this->factory->model->limitPage)
                break;

            // NOTE: Last page.
            if ((isset($lastPage) && $page === $lastPage) || $this->factory->getClientAlias() == 'file')
                break;

            // NOTE: Last product of every next page. When source returns non-empty results, even if last page is passed over.
            if (end($dataProducts) === end($data) && !$this->isConstantDefined('PAGER_EXCUSE')) 
                break;

            foreach ($data as $product) 
                $dataProducts[] = $product;
        }

        // NOTE: Save products to db
        $productUrls = $this->settler->saveProducts($dataProducts, $this->factory->model);

        // NOTE: Helpful data & logging
        $this->factory->model->warnings = $dataWarnings;
        $this->factory->model->products = $dataProducts;
        $this->factory->model->details  = $productUrls;         
        
        $this->log($time);
    }
}
