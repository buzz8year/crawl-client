<?php

namespace crawler\models\header;

use crawler\models\source\Source;
use yii\helpers\ArrayHelper;
use Yii;

class Header extends \yii\db\ActiveRecord
{

    public static function tableName()
    {
        return 'header';
    }


    public function rules()
    {
        return [
            [['title'], 'required'],
            [['title'], 'string', 'max' => 255],
        ];
    }


    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'title' => 'Title',
        ];
    }

    public static function listHeaders()
    {
        $headers = [];
        foreach (self::find()->all() as $header) {
            if (strpos(strtolower($header->title), 'user-agent') !== false) {
                $headers[$header->id] = $header->title;
            }
        }
        return $headers;
    }


    public function getHeaderSources()
    {
        return $this->hasMany(HeaderSource::class, ['header_id' => 'id'])->asArray();
    }


    public function getHeaderFullSources()
    {   
        $data = [];
        foreach ($this->headerSources as $source)
            $data[$source['source_id']] = Source::findOne($source['source_id']);

        return $data;
    }


    public function getHeaderValues()
    {
        return $this->hasMany(HeaderValue::class, ['header_id' => 'id'])->asArray();
    }


    public static function headerValues($id)
    {
        return ArrayHelper::map(HeaderValue::find()->where(['header_id' => $id])->all(), 'id', 'value');
    }

    public static function findAllActive()
    {
        return self::find()->where(['status' => 1])->all();
    }
}
