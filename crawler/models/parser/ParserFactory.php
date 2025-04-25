<?php

namespace crawler\models\parser;

use crawler\models\parser\Parser;
use crawler\models\source\Source;
use yii\base\DynamicModel;

class ParserFactory
{
    public $model;
    public $source;

    public $agents;
    public $proxies;
    public $options;

    public $client;
    public $parser;

    public bool $notVoid;


    public function createParser($sourceId)
    {
        $this->parser = new Parser($this);
        $this->source = Source::findOne($sourceId);

        $this->createModel();
        $this->parser = new $this->model->class($this);
        $this->parser->factory = $this;
    }

    public function createModel()
    {
        $model = new DynamicModel([
            'id'     => $this->source->id,
            'title'  => $this->source->title,
            'domain' => $this->source->source_url,
            'class'  => $this->source->class_namespace,
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

        $model->addRule(['class'], 'required');

        $this->model = $model;

        $this->setProxies();
        $this->setAgents();
        $this->setOptions();
        $this->setClient();
    }

    public function handleRequest(int $id, string $reg = '', string $cat = '', string $word = '', int $sale = 0)
    {
        $this->notVoid = $reg || $cat || $word;
        $this->model->categorySourceId = $cat ? $cat : 1;
        $this->model->regionId = $reg;

        if ($cat) 
        {
            // $categorySource = CategorySource::find()->where(['source_id' => $id, 'category_id' => $cat])->one();
            $categorySource = \crawler\models\category\CategorySource::findOne($cat);
            $categoryGlobal = \crawler\models\category\Category::findOne($categorySource->category_id);
            // $model->categorySourceId = $categorySource ? $categorySource->id : null;
            $this->model->categorySourceId = $cat;
            $this->model->categoryId = $categoryGlobal->id;
        }

        if ($word) 
        {
            $keyword = \crawler\models\keyword\Keyword::find()->where(['word' => $word])->one();
            $this->model->keywordId = $keyword ? $keyword->id : null;
        }

        if ($sale) 
            $this->model->saleFlag = true;

        // URL: Build
        if ($this->notVoid)
            $this->model->url = $this->parser->urlBuild($this->model->regionId, $this->model->categorySourceId ?? '', $word);
    }

    /**
     * @return void
     */
    public function setProxies()
    {
        $this->proxies = $this->source->proxies['ipv4'] ?? [];
    }

    /**
     * @return void
     */
    public function setAgents()
    {
        $this->agents = $this->source->headerValues['user-agent'] ?? [];
    }

    /**
     * @return void
     */
    public function setOptions()
    {
        $random  = $this->agents 
            ? rand(0, count($this->agents) - 1) 
            : null;

        $agent = $this->agents[$random] ?? [];

        $options = [
            CURLOPT_ENCODING       => '',
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => $this->model->class::CURL_FOLLOW,
            CURLOPT_PROXY          => $this->proxies[0]['address'] ?? null,
            CURLOPT_PROXYUSERPWD   => $this->proxies[0]['password'] ?? null,
            CURLOPT_HTTPHEADER     => $this->agents[0] ?? [],
        ];

        $this->options = $options;
    }

    /**
     * @return void
     */
    public function setClient(string $client = 'curl')
    {
        $constDefined = defined($this->model->class . '::DEFINE_CLIENT') 
            ? $this->model->class::DEFINE_CLIENT 
            : 'curl';

        $this->client['alias'] = $client ?? $constDefined;
    }

    /**
     * @return string
     */
    public function getClientAlias()
    {
        $constDefined = defined($this->model->class . '::DEFINE_CLIENT') 
            ? $this->model->class::DEFINE_CLIENT 
            : 'curl';

        return $this->client['alias'] ?? $constDefined;
    }


    /**
     * @return int or bool
     */
    public function getMaxQuantity()
    {
        return defined($this->model->class . '::MAX_QUANTITY') 
            ? $this->model->class::MAX_QUANTITY 
            : false;
    }

}