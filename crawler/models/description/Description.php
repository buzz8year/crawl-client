<?php

namespace crawler\models\description;

use crawler\models\product\Product;
use crawler\util\SettlingException;
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
            [['product_id'], 'exist', 'skipOnError' => true, 'targetClass' => Product::class, 'targetAttribute' => ['product_id' => 'id']],
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
        return $this->hasOne(Product::class, ['id' => 'product_id']);
    }

    public static function findExisting(int $productId, string $text) : ?self 
    {
        return Description::find()
            ->where(['product_id' => $productId, 'text_original' => $text])
            ->one();
    }

    public static function exists(int $productId, string $text) : bool 
    {
        return (bool) Description::find()
            ->where(['product_id' => $productId, 'text_original' => $text])
            ->scalar();
    }

    public static function create(int $productId, string $text,  string $title): Description
    {
        $model = new Description();
        $model->product_id = $productId;
        $model->text_original = $text;
        $model->title = $title;

        if (!$model->save()) 
            throw new SettlingException($model);

        return $model;
    }
}
