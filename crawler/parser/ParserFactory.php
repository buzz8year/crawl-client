<?php

namespace crawler\parser;

use crawler\models\category\Category;
use crawler\models\keyword\Keyword;
use crawler\parser\Parser;
use crawler\models\source\Source;
use yii\base\DynamicModel;

class ParserFactory
{
    public DynamicModel $model;
    public Source $source;
    public Parser $parser;
    public string $client;
    public array $options;
    public array $proxies;
    public array $agents;

    public function setParser($sourceId): void
    {
        $this->source = Source::findOne($sourceId);
        $this->setModel();
        
        $this->parser = new $this->model->class($this);
    }

    public function setModel(): void
    {
        $this->model = new ParserModel($this->source)->getModel();
        $this->client = $this->getDefinedClient();

        $this->setProxies();
        $this->setAgents();
        $this->setOptions();
    }

    /**
     * @return void
     */
    public function setProxies(): void
    {
        $this->proxies = $this->source->proxies['ipv4'] ?? [];
    }

    /**
     * @return void
     */
    public function setAgents(): void
    {
        $this->agents = $this->source->headerValues['user-agent'] ?? [];
    }

    /**
     * @return void
     */
    public function setOptions(): void
    {
        $random = $this->agents 
            ? rand(0, count($this->agents) - 1) 
            : null;
        // $agent = $this->agents[$random] ?? [];

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
     * @return string
     */
    public function getClientAlias(): string
    {
        return $this->client ?? $this->getDefinedClient();
    }

    /**
     * @return string
     */
    public function getDefinedClient(): string
    {
        return defined($this->model->class . '::DEFINE_CLIENT') 
            ? $this->model->class::DEFINE_CLIENT 
            : 'curl';
    }

    /**
     * @return int or bool
     */
    public function getDefinedMaxQuantity(): mixed
    {
        return defined($this->model->class . '::MAX_QUANTITY') 
            ? $this->model->class::MAX_QUANTITY 
            : false;
    }

    public function handleRequest(string $reg, string $cat, string $word, int $sale): void
    {
        $this->model->categorySourceId = $cat ?: 1;
        $this->model->regionId = $reg;

        if ($cat) 
        {
            $categoryGlobal = Category::findByCategorySourceId($cat);
            $this->model->categoryId = $categoryGlobal->id;
            $this->model->categorySourceId = $cat;
        }

        if ($word) 
            $this->model->keywordId = Keyword::getIdByWord($word);

        if ($sale) 
            $this->model->saleFlag = true;

        $categorySourceId = strval($this->model->categorySourceId ?? 0);

        $this->model->url = $this->parser->buildUrl(
            $this->model->regionId, 
            $categorySourceId, 
            $word
        );
    }
}