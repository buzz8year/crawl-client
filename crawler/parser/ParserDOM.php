<?php

namespace crawler\parser;

use crawler\parser\ParserFactory;

use SimpleXMLElement;
use DOMDocument;
use DOMXPath;

class ParserDOM 
{
    public ParserFactory $factory;

    public function __construct(ParserFactory $factory)
    {
        $this->factory = $factory;
    }

    public function getPlainXml(string $response)
    {
        return new SimpleXMLElement($response);;
    }

    public function getNodes(string $response, string $xpath)
    {
        $dom = new DOMDocument();
        $dom->formatOutput = true;
        $dom->preserveWhiteSpace = false;
        @$dom->loadHTML($response);

        $domxpath = new DOMXPath($dom);
        $nodes = $domxpath->query($this->factory->model->class::{strtoupper($xpath)});

        return $nodes;
    }

    public function getDetailNodes($response)
    {
        $data = [];

        if ($this->factory->parser->isConstantDefined('XPATH_DESCRIPTION')) 
            $data['description'] = $this->factory->parser->getDescriptionData($this->getNodes($response, 'XPATH_DESCRIPTION'));
        
        if ($this->factory->parser->isConstantDefined('XPATH_ATTRIBUTE')) 
            $data['attribute'] = $this->factory->parser->getAttributeData($this->getNodes($response, 'XPATH_ATTRIBUTE'));
        
        if ($this->factory->parser->isConstantDefined('XPATH_IMAGE')) 
            $data['image'] = $this->factory->parser->getImageData($this->getNodes($response, 'XPATH_IMAGE'));
        
        if ($this->factory->parser->isConstantDefined('XPATH_PRICE')) 
            $data['price'] = $this->factory->parser->getPriceData($this->getNodes($response, 'XPATH_PRICE'));

        return $data;
    }

    public function getDataByType($response, string $type)
    {
        if ($type == 'description')
            return $this->factory->parser->getDescriptionData($this->getNodes($response, 'XPATH_DESCRIPTION'));

        if ($type == 'attribute')
            return $this->factory->parser->getAttributeData($this->getNodes($response, 'XPATH_ATTRIBUTE'));

        if ($type == 'image')
            return $this->factory->parser->getImageData($this->getNodes($response, 'XPATH_IMAGE'));

        if ($type == 'price')
            return $this->factory->parser->getPriceData($this->getNodes($response, 'XPATH_PRICE'));
    }

    public function getNodesByType($nodes, string $type)
    {
        if ($type == 'description')
            return $this->factory->parser->getDescriptionData($nodes);

        if ($type == 'attribute')
            return $this->factory->parser->getAttributeData($nodes);

        if ($type == 'image')
            return $this->factory->parser->getImageData($nodes);

        if ($type == 'price')
            return $this->factory->parser->getPriceData($nodes);
    }
}