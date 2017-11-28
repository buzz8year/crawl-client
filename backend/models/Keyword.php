<?php

namespace backend\models;

use Yii;
use yii\helpers\ArrayHelper;
use backend\models\Source;


class Keyword extends \yii\db\ActiveRecord
{


    public static function tableName()
    {
        return 'keyword';
    }


    public function rules()
    {
        return [
            [['word'], 'required'],
            [['word'], 'string', 'max' => 72],
        ];
    }


    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'word' => 'Word',
        ];
    }


    public function getKeywordSources()
    {
        return $this->hasMany(KeywordSource::className(), ['keyword_id' => 'id']);
    }


    public function getProducts()
    {
        return $this->hasMany(Product::className(), ['source_word_id' => 'id']);
    }

    static function listKeywords() 
    {
        return ArrayHelper::map( self::find()->all(), 'id', 'word' );
    }

}
