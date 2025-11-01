<?php

namespace crawler\parser;

use crawler\models\source\Source;
use yii\base\DynamicModel;

class ParserModel
{
    private Source $source;
    private DynamicModel $model;

    public function __construct(Source $source)
    {
        $this->source = $source;
        $this->setModel();
    }
    
    public function getModel()
    {
        return $this->model;
    }

    private function setModel()
    {
        $this->model = new DynamicModel([
            'id' => $this->source->id,
            'title' => $this->source->title,
            'domain' => $this->source->source_url,
            'class' => $this->source->class_namespace,
            'limitDetail' => $this->source->limit_detail,
            'limitPage' => $this->source->limit_page,
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
        $this->model->addRule(['class'], 'required');
    }
}