<?php

namespace crawler\models\attribute;

use Yii;
use yii\helpers\ArrayHelper;


class Attribute extends \yii\db\ActiveRecord
{

    public static function tableName()
    {
        return 'attribute';
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


    static function listAttributes() 
    {
        return ArrayHelper::map( self::find()->all(), 'id', 'title' );
    }



    public function getAttributeValues()
    {
        return $this->hasMany(AttributeValue::className(), ['attribute_id' => 'id']);
    }


    public function getProductAttributes()
    {
        return $this->hasMany(ProductAttribute::className(), ['attribute_id' => 'id']);
    }
}
