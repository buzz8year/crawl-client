<?php

namespace backend\models;

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
        return $this->hasMany(ProductImage::className(), ['image_id' => 'id']);
    }
}
