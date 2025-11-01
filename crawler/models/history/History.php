<?php

namespace crawler\models\history;

use crawler\models\category\CategorySource;
use crawler\models\keyword\Keyword;
use crawler\models\source\Source;
use Yii;
use yii\base\DynamicModel;

class History extends \yii\db\ActiveRecord
{
    public const string STATUS_MAX         = 'Most likely the maximum returned by the resource in the front has been reached - check the request directly on the resource to confirm.';
    public const string STATUS_ZERO        = 'No messages found on the resource. No errors in the headers either.';
    public const string STATUS_DEPRECIABLE = 'The possibility of such requests will be deprecated in the future.';
    public const string STATUS_GENERAL     = 'General accounting of the request.';
    public const string STATUS_WARNING     = 'Messages on the resource: ';
    public const string STATUS_HEADER_FAIL = 'Bad user-agent header.';
    public const string STATUS_PROXY_FAIL  = 'Bad proxy.';

    public static function tableName()
    {
        return 'history';
    }

    public function rules()
    {
        return [
            [['date'], 'safe'],
            [['note'], 'string'],
            [['time'], 'number'],
            [['url'], 'string', 'max' => 255],
            [['client'], 'default', 'value'=> 0],

            [['source_id', 'category_source_id', 'keyword_id', 'item_quantity', 'proxy_source_id', 'header_source_id', 'status'], 'integer'],
            [['category_source_id'], 'exist', 'skipOnError' => true, 'targetClass' => CategorySource::class, 'targetAttribute' => ['category_source_id' => 'id']],
            [['keyword_id'], 'exist', 'skipOnError' => true, 'targetClass' => Keyword::class, 'targetAttribute' => ['keyword_id' => 'id']],
            [['source_id'], 'exist', 'skipOnError' => true, 'targetClass' => Source::class, 'targetAttribute' => ['source_id' => 'id']],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id'                 => 'ID',
            'date'               => 'Date',
            'url'                => 'Url',
            'source_id'          => 'Source',
            'category_source_id' => 'Category',
            'keyword_id'         => 'Keyword',
            'proxy_source_id'    => 'Proxy Source',
            'header_source_id'   => 'Header Source',
            'item_quantity'      => 'Item Quantity',
            'status'             => 'Status',
            'note'               => 'Note',
        ];
    }


    public function getCategorySource()
    {
        return $this->hasOne(CategorySource::class, ['id' => 'category_source_id']);
    }

    public function getKeyword()
    {
        return $this->hasOne(Keyword::class, ['id' => 'keyword_id']);
    }

    // public function getProxySourceCheck() {
    //     return $this->hasOne(ProxySourceCheck::class, ['id' => 'proxy_source_check_id']);
    // }

    // public function getProxySource() {
    //     return $this->hasOne(ProxySource::class, ['id' => 'proxy_source_id']);
    // }

    public function getSource()
    {
        return $this->hasOne(Source::class, ['id' => 'source_id']);
    }

    public static function createByParserModel(DynamicModel $model, float $time)
    {
        $history                     = new self();
        $history->url                = $model->url;
        $history->source_id          = $model->id;
        $history->category_source_id = $model->categorySourceId;
        $history->item_quantity      = count($model->products);
        $history->keyword_id         = $model->keywordId;
        $history->time               = $time;

        switch (true) 
        {
            case $model->warnings:
                $history->status = 5;
                $history->note = History::STATUS_WARNING . implode(' - ', $model->warnings);

            case $history->item_quantity == 0:
                $history->status = 0;
                $history->note = History::STATUS_ZERO;

            default: $history->status = 1;
        }
        $history->save();
    }
}
