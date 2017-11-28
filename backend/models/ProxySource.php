<?php

namespace backend\models;

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
            [['proxy_id'], 'exist', 'skipOnError' => true, 'targetClass' => Proxy::className(), 'targetAttribute' => ['proxy_id' => 'id']],
            [['source_id'], 'exist', 'skipOnError' => true, 'targetClass' => Source::className(), 'targetAttribute' => ['source_id' => 'id']],
        ];
    }


    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'source_id' => 'Source ID',
            'proxy_id' => 'Proxy ID',
            'fail_counter' => 'Fail Counter',
        ];
    }


    public function getProxy()
    {
        return $this->hasOne(Proxy::className(), ['id' => 'proxy_id']);
    }


    public function getSource()
    {
        return $this->hasOne(Source::className(), ['id' => 'source_id']);
    }


    public function getProxySourceChecks()
    {
        return $this->hasMany(ProxySourceCheck::className(), ['proxy_source_id' => 'id']);
    }
}
