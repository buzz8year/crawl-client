<?php

namespace crawler\models\header;

use Yii;

/**
 * This is the model class for table "header_source".
 *
 * @property int $id
 * @property int $header_id
 * @property int $header_value_id
 * @property int $source_id
 * @property int $off_counter
 *
 * @property Header $header
 * @property HeaderValue $headerValue
 * @property Source $source
 * @property HeaderSourceCheck[] $headerSourceChecks
 */
class HeaderSource extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'header_source';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['header_id', 'header_value_id', 'source_id'], 'required'],
            [['header_id', 'header_value_id', 'source_id', 'queue', 'status', 'fail_counter'], 'integer'],
            [['header_id'], 'exist', 'skipOnError' => true, 'targetClass' => Header::className(), 'targetAttribute' => ['header_id' => 'id']],
            [['header_value_id'], 'exist', 'skipOnError' => true, 'targetClass' => HeaderValue::className(), 'targetAttribute' => ['header_value_id' => 'id']],
            [['source_id'], 'exist', 'skipOnError' => true, 'targetClass' => Source::className(), 'targetAttribute' => ['source_id' => 'id']],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'header_id' => 'Header ID',
            'header_value_id' => 'Header Value ID',
            'source_id' => 'Source ID',
            'off_counter' => 'Off Counter',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getHeader()
    {
        return $this->hasOne(Header::className(), ['id' => 'header_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getHeaderValue()
    {
        return $this->hasOne(HeaderValue::className(), ['id' => 'header_value_id']);
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
    public function getHeaderSourceChecks()
    {
        return $this->hasMany(HeaderSourceCheck::className(), ['header_source_id' => 'id']);
    }
}
