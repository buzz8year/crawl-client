<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "proxy_source_check".
 *
 * @property int $id
 * @property int $proxy_source_id
 * @property string $check_date
 * @property int $status
 *
 * @property ProxySource $proxySource
 */
class ProxySourceCheck extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'proxy_source_check';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['proxy_source_id', 'status'], 'required'],
            [['proxy_source_id', 'status'], 'integer'],
            [['check_date'], 'safe'],
            [['proxy_source_id'], 'exist', 'skipOnError' => true, 'targetClass' => ProxySource::className(), 'targetAttribute' => ['proxy_source_id' => 'id']],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'proxy_source_id' => 'Proxy Source ID',
            'check_date' => 'Check Date',
            'status' => 'Status',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getProxySource()
    {
        return $this->hasOne(ProxySource::className(), ['id' => 'proxy_source_id']);
    }
}
