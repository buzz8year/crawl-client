<?php

namespace crawler\util;

use ReflectionClass;

class SettlingException extends \Exception
{
    private $model;

    public function __construct($model)
    {
        $this->model = $model;
        $type = new ReflectionClass($model)->getShortName();

        switch($type) 
        {
            case 'Image': $this->setImageMessage();
            case 'Product': $this->setProductMessage();
            case 'ProductImage': $this->setProductImageMessage();
            case 'ProductAttribute': $this->setProductAttributeMessage();
            case 'AttributeValue': $this->setAttributeValueMessage();
            case 'Description': $this->setProductImageMessage();
            case 'History': $this->setHistoryMessage();
        }
    }

    public function setHistoryMessage()
    {
        $this->message = 'Error attempting to save product of URL: ' .
            PHP_EOL . '$newProduct->source_url         = ' . $this->model->source_url .
            PHP_EOL . '$newProduct->source_id          = ' . $this->model->source_id .
            PHP_EOL . '$newProduct->category_id        = ' . $this->model->category_id .
            PHP_EOL . '$newProduct->category_source_id = ' . $this->model->category_source_id .
            PHP_EOL . '$newProduct->keyword_id         = ' . $this->model->keyword_id .
            PHP_EOL . '$newProduct->region_id          = ' . $this->model->region_id .
            PHP_EOL . '$newProduct->title              = ' . $this->model->title .
            PHP_EOL . '$newProduct->price              = ' . $this->model->price;
    }

    public function setProductMessage()
    {
        $this->message = 'Error attempting to save product image: ' .
            PHP_EOL . '$newProductImage->image_id = ' . $this->model->id .
            PHP_EOL . '$newProductImage->product_id = ' . $this->model->product_id;
    }

    public function setAttributeValueMessage()
    {
        $this->message = 'Error attempting to save product image: ' .
            PHP_EOL . '$newValue->attribute_id = ' . $this->model->id .
            PHP_EOL . '$newValue->value = ' . $this->model->value;
    }

    public function setProductAttributeMessage()
    {
        $this->message = 'Error attempting to save product image: ' .
            PHP_EOL . '$newProductAttribute->attribute_id = ' . $this->model->attribute_id .
            PHP_EOL . '$newProductAttribute->attribute_value_id = ' . $this->model->attribute_value_id .
            PHP_EOL . '$newProductAttribute->product_id = ' . $this->model->product_id;
    }

    public function setProductImageMessage()
    {
        $this->message = 'Error attempting to save product image: ' .
            PHP_EOL . '$newProductImage->image_id = ' . $this->model->id .
            PHP_EOL . '$newProductImage->product_id = ' . $this->model->product_id;
    }

    public function setImageMessage()
    {
        $this->message = 'Error attempting to save image (standalone): ' .
            PHP_EOL . '$newImage->source_url = ' . $this->model->source_url;
    }

    public function setDescriptionMessage()
    {
        $this->message = 'Error attempting to save product image: ' .
            PHP_EOL . '$newDesc->text_original = ' . $this->model->text_original .
            PHP_EOL . '$newDesc->product_id = ' . $this->model->product_id .
            PHP_EOL . '$newDesc->title = ' . $this->model->title;
    }
}