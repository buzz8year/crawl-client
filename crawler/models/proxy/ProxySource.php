<?php

namespace crawler\models\proxy;

use crawler\models\source\Source;
use Yii;

class ProxySource extends \yii\db\ActiveRecord
{
    public static function tableName()
    {
        return 'proxy_source';
    }

    public function rules()
    {
        return [
            [['source_id', 'proxy_id'], 'required'],
            [['source_id', 'proxy_id', 'fail_counter', 'status', 'queue'], 'integer'],
            [['proxy_id'], 'exist', 'skipOnError' => true, 'targetClass' => Proxy::class, 'targetAttribute' => ['proxy_id' => 'id']],
            [['source_id'], 'exist', 'skipOnError' => true, 'targetClass' => Source::class, 'targetAttribute' => ['source_id' => 'id']],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'fail_counter' => 'Fail Counter',
            'source_id' => 'Source ID',
            'proxy_id' => 'Proxy ID',
        ];
    }

    public function getProxy()
    {
        return $this->hasOne(Proxy::class, ['id' => 'proxy_id']);
    }

    public function getSource()
    {
        return $this->hasOne(Source::class, ['id' => 'source_id']);
    }

    public function getProxySourceChecks()
    {
        return $this->hasMany(ProxySourceCheck::class, ['proxy_source_id' => 'id']);
    }
}
