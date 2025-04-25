<?php

namespace crawler\models\header;

use Yii;

/**
 * This is the model class for table "header_value".
 *
 * @property int $id
 * @property int $header_id
 * @property string $value
 *
 * @property HeaderSource[] $headerSources
 * @property Header $header
 */
class HeaderValue extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'header_value';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['header_id', 'value'], 'required'],
            [['header_id'], 'integer'],
            [['value'], 'string', 'max' => 255],
            [['header_id'], 'exist', 'skipOnError' => true, 'targetClass' => Header::className(), 'targetAttribute' => ['header_id' => 'id']],
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
            'value' => 'Value',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getHeaderSources()
    {
        return $this->hasMany(HeaderSource::className(), ['header_value_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getHeader()
    {
        return $this->hasOne(Header::className(), ['id' => 'header_id']);
    }
}
