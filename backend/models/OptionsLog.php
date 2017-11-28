<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "proxy_header_check".
 *
 * @property int $id
 * @property int $source_id
 * @property int $header_value_id
 * @property int $proxy_id
 * @property string $client
 * @property string $date
 *
 * @property Proxy $proxy
 * @property Source $source
 * @property HeaderValue $headerValue
 */
class OptionsLog extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'options_log';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['source_id', 'client'], 'required'],
            [['source_id', 'header_value_id', 'proxy_id'], 'integer'],
            [['date'], 'safe'],
            [['url'], 'string'],
            [['client'], 'string', 'max' => 32],
            [['proxy_id'], 'exist', 'skipOnError' => true, 'targetClass' => Proxy::className(), 'targetAttribute' => ['proxy_id' => 'id']],
            [['source_id'], 'exist', 'skipOnError' => true, 'targetClass' => Source::className(), 'targetAttribute' => ['source_id' => 'id']],
            [['header_value_id'], 'exist', 'skipOnError' => true, 'targetClass' => HeaderValue::className(), 'targetAttribute' => ['header_value_id' => 'id']],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'url' => 'URL',
            'source_id' => 'Source',
            'header_value_id' => 'Header Value',
            'proxy_id' => 'Proxy',
            'client' => 'Client',
            'date' => 'Date',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getProxy()
    {
        return $this->hasOne(Proxy::className(), ['id' => 'proxy_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSource()
    {
        return $this->hasOne(Source::className(), ['id' => 'source_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getHeaderValue()
    {
        return $this->hasOne(HeaderValue::className(), ['id' => 'header_value_id']);
    }
}
