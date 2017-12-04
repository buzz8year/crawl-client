<?php

namespace backend\models;

use backend\models\morph\Morph;
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


    static function listUniques(int $nest = 0)
    {   
        $data = [];
        foreach (self::find()->where(['nest_level' => $nest])->all() as $category) {
            $tags = implode('+', $category->tags);
            $data[$tags] = isset($data[$tags]) ? ($data[$tags] + 1) : 1;
        }
        return $data;
    }

    public function getTags() {
        // // if ($this->self_parent_id) {
        //     // $parent = Category::findOne($this->self_parent_id);
        //     // return $morphier->getPhraseLemmas($parent->title) . '+' . $morphier->getPhraseLemmas($this->category->title);
        // // } else {
        //     // return $morphier->getPhraseLemmas($this->category->title);
        //     $lemmas = $this->getRecursiveLemmas($this->id);
        //     // natsort($lemmas);
        //     return $lemmas;
        //     // return array_reverse($lemmas);
        // // }

        if ($tagsImplode = $this->category->tags) {
            $tags = [];
            $exp = explode('+', $tagsImplode);
            foreach ($exp as $tagId) {
                $tag = CategoryTags::findOne($tagId);
                $tags[] = $tag->tag;
            }
            return $tags;
        }

        return [];

    }


    // // public function getRecursiveLemmas(int $categoryId, string $lemmas = '')
    // public function getRecursiveLemmas(int $categoryId, array $lemmas = [])
    // {
    //     $morphier = new Morph('ru');
    //     $categorySource = CategorySource::findOne($categoryId);
    //     $category = Category::findOne($categorySource->category_id);
    //     if ($category->title !== '--') {
    //         // $lemmas = $morphier->getPhraseLemmas($category->title) . ($lemmas ? ('+' . $lemmas) : '');
    //         $newLemmas = $morphier->getPhraseLemmas($category->title, 1);
    //         // $newLemmas = $morphier->getPhraseLemmas($category->title);
    //         foreach ($newLemmas as $key => $newLemma) {
    //             if (!in_array($newLemma, $lemmas)) {
    //                 $lemmas[] = $newLemma;
    //                 // array_unshift($lemmas, $newLemma);
    //             }
    //         }
    //     }
    //     if ($parentId = CategorySource::findOne($categoryId)->self_parent_id) {
    //         return $this->getRecursiveLemmas($parentId, $lemmas);
    //     }
    //     // return $lemmas;
    //     return array_reverse($lemmas);
    // }

    public function getSource()
    {
        return $this->hasOne(Source::className(), ['id' => 'source_id']);
    }


}
