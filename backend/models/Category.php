<?php

namespace backend\models;

use yii\helpers\ArrayHelper;
use Yii;


class Category extends \yii\db\ActiveRecord
{

    public static function tableName()
    {
        return 'category';
    }


    public function rules()
    {
        return [
            [['title'], 'required'],
            [['id', 'category_outer_id', 'status'], 'integer'],
            [['title', 'tags'], 'string', 'max' => 255],
        ];
    }


    public function attributeLabels()
    {
        return [
            'category_id' => 'Cat. ID',
            'category_outer_id' => 'Category Outer ID',
            'title' => 'Title',
            'tags' => 'Tags',
        ];
    }

    static function listCategories() 
    {
        return ArrayHelper::map( self::find()->all(), 'id', 'title' );
    }


    public function getCategorySources()
    {
        return $this->hasMany(CategorySource::className(), ['category_id' => 'id']);
    }

    public function getSources()
    {
        $dataSources = [];
        foreach ($this->categorySources as $categorySource) {
            $dataSources[$categorySource->id] = Source::findOne($categorySource->source_id)->title;
        }
        return $dataSources;
    }

    public function getProducts()
    {
        return $this->hasMany(Product::className(), ['category_id' => 'id']);
    }

    static function countTagUsage()
    {   
        $data = [];
        foreach (self::find()->all() as $category) {
            if ($category->tags) {
                foreach (explode('+', $category->tags) as $tagId) {
                    $tag = CategoryTags::findOne($tagId)->tag;
                    $data[$tag] = isset($data[$tag]) ? ($data[$tag] + 1) : 1;
                }
            }
        }
        natsort($data);
        return array_reverse($data);
    }
}
