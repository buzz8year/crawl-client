<?php

namespace crawler\models\image;

use crawler\models\product\ProductImage;
use Yii;

class Image extends \yii\db\ActiveRecord
{
    public static function tableName()
    {
        return 'image';
    }

    public function rules()
    {
        return [
            [['source_url'], 'required'],
            [['path', 'source_url'], 'string', 'max' => 255],
            [['self_parent_id'], 'integer'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'path' => 'Path',
        ];
    }

    public function getProductImages()
    {
        return $this->hasMany(ProductImage::class, ['image_id' => 'id']);
    }

    public static function findOneByUrl(string $url)
    {
        return Image::find()
            ->where(['source_url' => $url])
            ->one();
    }

    public static function findOneAsArray(int $id)
    {
        return Image::find()
            ->where(['id' => $id])
            ->asArray()
            ->one();
    }

    public static function findThumbAsArray(int $id)
    {
        return Image::find()
            ->where(['id' => $id])
            ->andWhere(['not', ['self_parent_id' => null]])
            ->asArray()
            ->one();
    }
}
