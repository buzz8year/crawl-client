<?php

namespace backend\models;

use Yii;


class CategorySource extends \yii\db\ActiveRecord
{

    public static function tableName()
    {
        return 'category_source';
    }


    public function rules()
    {
        return [
            // [['category_id', 'source_id', 'source_url'], 'required'],
            [['category_id', 'source_id'], 'required'],
            [['source_url', 'source_url_dump', 'source_category_alias'], 'string'],
            [['category_id', 'source_id', 'self_parent_id', 'source_category_id'], 'integer'],
            [['category_id'], 'exist', 'skipOnError' => true, 'targetClass' => Category::className(), 'targetAttribute' => ['category_id' => 'id']],
            [['source_id'], 'exist', 'skipOnError' => true, 'targetClass' => Source::className(), 'targetAttribute' => ['source_id' => 'id']],
        ];
    }


    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'category_id' => 'Global Category',
            'source_id' => 'Source',
            'source_url' => 'Source Url',
            'source_url_dump' => 'Source Url Dump',
            'source_category_alias' => 'Category Alias',
        ];
    }


    public function getCategory()
    {
        return $this->hasOne(Category::className(), ['id' => 'category_id']);
    }


    public function getSource()
    {
        return $this->hasOne(Source::className(), ['id' => 'source_id']);
    }
}
