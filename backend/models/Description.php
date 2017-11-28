<?php

namespace backend\models;

use Yii;


class Description extends \yii\db\ActiveRecord
{

    public static function tableName()
    {
        return 'product_description';
    }


    public function rules()
    {
        return [
            [['product_id', 'text_original'], 'required'],
            [['product_id', 'status'], 'integer'],
            [['text_original', 'text_synonymized'], 'string'],
            [['title'], 'string', 'max' => 128],
            [['product_id'], 'exist', 'skipOnError' => true, 'targetClass' => Product::className(), 'targetAttribute' => ['product_id' => 'id']],
        ];
    }


    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'product_id' => 'Product ID',
            'title' => 'Title',
            'text_original' => 'Text Original',
            'text_synonymized' => 'Text Synonymized',
            'status' => 'Status',
        ];
    }


    public function getProduct()
    {
        return $this->hasOne(Product::className(), ['id' => 'product_id']);
    }
}
