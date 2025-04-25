<?php

namespace crawler\models\header;

use Yii;

/**
 * This is the model class for table "header_source_check".
 *
 * @property int $id
 * @property int $header_source_id
 * @property string $check_date
 * @property int $status
 *
 * @property HeaderSource $headerSource
 */
class HeaderSourceCheck extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'header_source_check';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['header_source_id', 'status'], 'required'],
            [['header_source_id', 'status'], 'integer'],
            [['check_date'], 'safe'],
            [['header_source_id'], 'exist', 'skipOnError' => true, 'targetClass' => HeaderSource::className(), 'targetAttribute' => ['header_source_id' => 'id']],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'header_source_id' => 'Header Source ID',
            'check_date' => 'Check Date',
            'status' => 'Status',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getHeaderSource()
    {
        return $this->hasOne(HeaderSource::className(), ['id' => 'header_source_id']);
    }
}
