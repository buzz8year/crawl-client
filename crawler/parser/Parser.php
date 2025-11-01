<?php

namespace crawler\parser;

use crawler\parser\enum\DetailsType;
use crawler\parser\enum\PagerEnum;
use crawler\parser\enum\ParserType;
use crawler\parser\enum\SessionType;
use crawler\parser\enum\XpathType;
use crawler\parser\interface\ParserInterface;
use crawler\parser\ParserSettler;
use crawler\models\history\History;

/**x
 * Parser prepares the ground for & implements parsing works feeding a specific parser class related to the source.
 */
abstract class Parser implements ParserInterface
{
    public ParserFactory $factory;
    public ParserStrategy $strategy;
    public ParserSettler $settler;
    public ParserDOM $dom;

    public function __construct(ParserFactory $factory)
    {
        $this->factory = $factory;
        $this->strategy = new ParserStrategy($this->factory);
        $this->settler = new ParserSettler($this->factory);
        $this->dom = new ParserDOM($this->factory);
    }

    // NOTE: Main loop. Catalog parsing happens here. 
    // WARNING: Watch the base/break cases! Otherwise loops infinitely.
    public function run(): void
    {
        $time = microtime(true);
        $dataProducts = [];

        $url = $this->factory->model->url;
        $lastPage = $this->getLastPage();
        $page = 0;

        while ($data = $this->parse(ParserType::CATALOG, $url, $this->pageQuery(++$page, $url))) 
        {
            // NOTE: If limit setting exceeded.
            if ($page > $this->factory->model->limitPage)
                break;

            // NOTE: Last page.
            if ((isset($lastPage) && $page === $lastPage) || $this->factory->getClientAlias() == SessionType::FILE)
                break;

            // NOTE: Last product of every next page. When source returns non-empty results, even if last page is passed over.
            if (end($dataProducts) === end($data) && !$this->isConstantDefined(PagerEnum::EXCUSE)) 
                break;

            foreach ($data as $product) 
                $dataProducts[] = $product;
        }

        $this->factory->model->products = $dataProducts;
        $this->factory->model->details = $this->settler->saveProducts($dataProducts);         
        
        History::createByParserModel($this->factory->model, microtime(true) - $time);
    }

    /**
     * NOTE: parse() function determines the "type" of parsing needed, and further related processing, recieved from curl request.
     * IMPORTANT: curlSession() needs to be called separately for every "type" condition, 
     * since we need to recreate the "options" for the session, and if it would be called only once, 
     * above all conditions, we would not be able to determine if "options" are legitimate or not.
     *
     * @param string $type
     * @param string $url
     * @param string $page, default empty string
     */
    public function parse(string $type, string $url, string $page = '')
    {
        if ($this->isTypeCatalog($type))
            return $this->parseCatalog($url, $page);

        if ($this->isTypeDetails($type)) 
            return $this->parseDetails();

        if ($this->isTypeWarning($type)) 
            return $this->parseWarnings($url);

        // if ($this->isConstantDefined('XPATH_SUPER')) 
        //     return $this->parseSuper($type, $response);
        return $this->parseByType($type, $url);
    }
    
    public function parseByType(string $type, string $url)
    {
        $response = $this->strategy->runClientSession($url);
        if (empty($response))
            return null;

        return $this->dom->getDataByType($response, $type);
    }

    public function parseCatalog(string $url, string $page = ''): array
    {
        // NOTE: When pagination string is somewhere in between of the url
        $pageUrl = !$this->isConstantDefined(PagerEnum::SPLIT_URL)
            ? sprintf('%s%s', $url, $page)
            : $page;

        $response = $this->factory->getClientAlias() != SessionType::FILE 
            ? $this->strategy->runClientSession($pageUrl)
            : $this->strategy->zipSession();

        if (empty($response)) 
            return [[]];

        if ($this->factory->getClientAlias() == SessionType::FILE) 
            return $this->getProducts($this->dom->getPlainXml($response));

        // NOTE: When Search page & Catalog page templates are different
        $xpath = $this->isTypeSearch() 
            ? XpathType::SEARCH 
            : XpathType::CATALOG;

        $nodes = $this->dom->getNodes($response, $xpath);
        if (isset($nodes->length) && $nodes->length)
            return $this->getProducts($nodes);

        return [[]];
    }

    /**
     * parseDetails() parses products' details
     * @param array $products - products array with id-url pairs
     */
    public function parseDetails()
    {
        if (empty($this->factory->model->details)) 
            return;

        $data = [];
        foreach ($this->factory->model->details as $id => $url) 
        {
            $response = $this->strategy->runClientSession($url);
            if (empty($response))
                continue;

            $data[$id] = $this->dom->getDetailNodes($response);

            foreach (DetailsType::SETTLE_METHODS as $type => $method) 
                $this->settler->{$method}($data[$id][$type], (int)$id);
        }
        return $data;
    }

    public function parseWarnings(string $url)
    {
        $response = $this->strategy->runClientSession($url);
        $nodes = $this->dom->getNodes($response, XpathType::WARNING);

        if ($nodes->length)
            return $this->getWarningData($nodes);
    }

    public function isTypeWarning(string $type): bool
    {
        return $type == ParserType::WARNING 
            && $this->isConstantDefined(XpathType::WARNING);
    }

    public function isTypeDetails(string $type): bool
    {
        return $type == ParserType::DETAILS 
            && $this->isConstantDefined(XpathType::DETAILS);
    }

    public function isTypeCatalog(string $type): bool
    {
        return $type === ParserType::CATALOG 
            && $this->isConstantDefined(XpathType::CATALOG);
    }

    public function isTypeSearch(): bool
    {
        return isset($this->factory->model->class::$template) 
            && $this->factory->model->class::$template == ParserType::SEARCH  
            && $this->isConstantDefined(XpathType::SEARCH);
    }

    public function isConstantDefined(string $constant): bool
    {
        return defined($this->factory->model->class . '::' . $constant) 
            && $this->factory->model->class::{$constant};
    }

    public function handleUrl(string $url)
    {   
        return $this->factory->source->getUrl($url);
    }

    public function getLastPage(): ?int
    {
        if (!$this->isConstantDefined(XpathType::PAGER)
            || !method_exists($this->factory->model->class, PagerEnum::LAST_PAGE_METHOD))
            return null;

        $nodes = $this->dom->getNodes($this->strategy->curlSession(), XpathType::PAGER);
        return $this->factory->model->class::lastPage($nodes);
    }
}
