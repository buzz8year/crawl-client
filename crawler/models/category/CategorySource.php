<?php

namespace crawler\models\category;

use crawler\models\morph\Morph;
use crawler\models\source\Source;
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
            [['category_id'], 'exist', 'skipOnError' => true, 'targetClass' => Category::class, 'targetAttribute' => ['category_id' => 'id']],
            [['source_id'], 'exist', 'skipOnError' => true, 'targetClass' => Source::class, 'targetAttribute' => ['source_id' => 'id']],
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
        return $this->hasOne(Category::class, ['id' => 'category_id']);
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

    public function getTags() 
    {
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

        if ($tagsImplode = $this->category->tags) 
        {
            $tags = [];
            $exp = explode('+', $tagsImplode);
            foreach ($exp as $tagId) 
            {
                $tag = CategoryTags::findOne($tagId);
                $tags[] = $tag->tag;
            }
            return $tags;
        }

        return [];

    }

    public function getSource()
    {
        return $this->hasOne(Source::class, ['id' => 'source_id']);
    }

    // public function getRecursiveLemmas(int $categoryId, array $lemmas = []) {
    //     $morphier = new Morph('ru');
    //     $categorySource = CategorySource::findOne($categoryId);
    //     $category = Category::findOne($categorySource->category_id);
    //     if ($category->title !== '--') {
    //         $newLemmas = $morphier->getPhraseLemmas($category->title, 1);
    //         foreach ($newLemmas as $key => $newLemma)
    //             if (!in_array($newLemma, $lemmas))
    //                 $lemmas[] = $newLemma;
    //     }
    //     if ($parentId = CategorySource::findOne($categoryId)->self_parent_id)
    //         return $this->getRecursiveLemmas($parentId, $lemmas);
    //     return array_reverse($lemmas);
    // }

    public static function getMaxNestLevelBySourceId(int $sourceId)
    {
        return CategorySource::find()
            ->where('source_id = ' . $sourceId)
            ->max('nest_level');
    }

    public static function findBySourceIdAndUrl(int $sourceId, string $url):? self
    {
        return self::find()->where(['source_id' => $sourceId, 'source_url' => $url])->one();
    }

    public static function findExisting(int $categoryId, int $sourceId, string $href):? self
    {
        return self::find()->where(['category_id' => $categoryId, 'source_id' => $sourceId, 'source_url' => $href])->one();
    }

    public static function findAllAsArrayById(int $id): array
    {
        return self::find()
            ->where(['source_id' => $id])
            ->orderBy('nest_level')
            ->asArray()
            ->all();
    }

    public static function createByCategoryArray(array $categoryArray, int $categoryId, int $parentId, int $sourceId): ?self
    {
        $new = new self();
        $new->category_id = $categoryId;
        $new->source_url = $categoryArray['href'];
        $new->source_category_id = $categoryArray['csid'] ?? null;
        $new->source_category_alias = $categoryArray['alias'] ?? '';
        $new->source_url_dump = $categoryArray['dump'] ?? '';
        $new->nest_level = $categoryArray['nest_level'];
        $new->self_parent_id = $parentId;
        $new->source_id  = $sourceId;

        if (!$new->save())
            Yii::error($new->errors, 'error-settler-nest-category-source-new-save');

        return $new;
    }
}
