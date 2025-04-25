<?php

namespace crawler\models\history;

use crawler\models\category\CategorySource;
use crawler\models\keyword\Keyword;
use crawler\models\source\Source;
use Yii;

class History extends \yii\db\ActiveRecord
{
    const STATUS_MAX         = 'Скорее всего достигнут максимум отдаваемый ресурсом во фронт - проверьте запрос непосредственно на ресурсе, чтобы убедиться.';
    const STATUS_ZERO        = 'Сообщений на ресурсе не найдено. Ошибок в заголовках также нет.';
    const STATUS_DEPRECIABLE = 'Возможность подобных запросов в будущем будет упразднена.';
    const STATUS_WARNING     = 'Сообщения на ресурсе: ';
    const STATUS_HEADER_FAIL = 'Плохой заголовок user-agent.';
    const STATUS_PROXY_FAIL  = 'Плохой прокси.';
    const STATUS_GENERAL     = 'Учет обращения.';

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

    // public function getProxySourceCheck()
    // {
    //     return $this->hasOne(ProxySourceCheck::class, ['id' => 'proxy_source_check_id']);
    // }

    // public function getProxySource()
    // {
    //     return $this->hasOne(ProxySource::class, ['id' => 'proxy_source_id']);
    // }

    public function getSource()
    {
        return $this->hasOne(Source::class, ['id' => 'source_id']);
    }
}
