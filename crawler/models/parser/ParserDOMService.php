<?php

namespace crawler\models\parser;

use crawler\models\parser\ParserFactory;

class ParserDOMService 
{
    public ParserFactory $factory;

    public function __construct(ParserFactory $factory)
    {
        $this->factory = $factory;
    }

    public function getPlainXml(string $response)
    {
        $xml = new \SimpleXMLElement($response);
        return $xml;
    }

    public function getNodes(string $response, string $xpathQuery, string $xpathSale = '')
    {
        $dom = new \DOMDocument();
        $dom->formatOutput = true;
        $dom->preserveWhiteSpace = false;
        @$dom->loadHTML($response);

        $xpath = new \DOMXPath($dom);
        $nodes = $xpath->query($xpathQuery);

        // return $nodes;
        if (!boolval($xpathSale))
            return $nodes;

        else {
            $sales = $xpath->query($xpathSale);

            if ($sales->length)
                return $sales;

            elseif ($nodes->length)
                return true;
        }
    }

    public function getDetailNodes($response)
    {
        $data = [];

        if ($this->factory->parser->isConstantDefined('XPATH_DESCRIPTION')) 
            $data['description'] = $this->factory->parser->getDescriptionData($this->getNodes($response, $this->factory->model->class::XPATH_DESCRIPTION));
        
        if ($this->factory->parser->isConstantDefined('XPATH_ATTRIBUTE')) 
            $data['attribute'] = $this->factory->parser->getAttributeData($this->getNodes($response, $this->factory->model->class::XPATH_ATTRIBUTE));
        
        if ($this->factory->parser->isConstantDefined('XPATH_IMAGE')) 
            $data['image'] = $this->factory->parser->getImageData($this->getNodes($response, $this->factory->model->class::XPATH_IMAGE));
        
        if ($this->factory->parser->isConstantDefined('XPATH_PRICE')) 
            $data['price'] = $this->factory->parser->getPriceData($this->getNodes($response, $this->factory->model->class::XPATH_PRICE));

        return $data;
    }

    public function getDataByType(string $type, $response)
    {
        if ($type == 'description')
            return $this->factory->parser->getDescriptionData($this->getNodes($response, $this->factory->model->class::XPATH_DESCRIPTION));

        elseif ($type == 'attribute')
            return $this->factory->parser->getAttributeData($this->getNodes($response, $this->factory->model->class::XPATH_ATTRIBUTE));

        elseif ($type == 'image')
            return $this->factory->parser->getImageData($this->getNodes($response, $this->factory->model->class::XPATH_IMAGE));

        elseif ($type == 'price')
            return $this->factory->parser->getPriceData($this->getNodes($response, $this->factory->model->class::XPATH_PRICE));
    }

    public function getNodesByType(string $type, $nodes)
    {
        if ($type == 'description')
            return $this->factory->parser->getDescriptionData($nodes);

        elseif ($type == 'attribute')
            return $this->factory->parser->getAttributeData($nodes);

        elseif ($type == 'image')
            return $this->factory->parser->getImageData($nodes);

        elseif ($type == 'price')
            return $this->factory->parser->getPriceData($nodes);
    }
}