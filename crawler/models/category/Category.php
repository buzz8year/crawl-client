<?php

namespace crawler\models\category;

use yii\web\NotFoundHttpException;
use crawler\models\product\Product;
use crawler\models\source\Source;
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

    public static function listCategories() 
    {
        return ArrayHelper::map(self::find()->all(), 'id', 'title');
    }


    public function getCategorySources()
    {
        return $this->hasMany(CategorySource::class, ['category_id' => 'id']);
    }

    public function getSources()
    {
        $dataSources = [];
        foreach ($this->categorySources as $categorySource)
            $dataSources[$categorySource->id] = Source::findOne($categorySource->source_id)->title;

        return $dataSources;
    }

    public function getProducts()
    {
        return $this->hasMany(Product::class, ['category_id' => 'id']);
    }

    public function saveTags($tags)
    {
        $this->tags = $tags;
        if (!$this->save())
            Yii::error($this->errors, 'error-save-tags');
    }

    public static function countTagUsage()
    {   
        $data = [];
        foreach (self::find()->all() as $category) 
        {
            if ($category->tags) 
            {
                foreach (explode('+', $category->tags) as $tagId) 
                {
                    $tag = CategoryTags::findOne($tagId)->tag;
                    $data[$tag] = isset($data[$tag]) ? ($data[$tag] + 1) : 1;
                }
            }
        }
        natsort($data);
        return array_reverse($data);
    }

    public static function createByTagsAndTitle(string $tags, string $title): ?self
    {
        $category = new self();
        $category->title = trim($title);
        $category->tags = trim($tags);

        if (!$category->save())
            Yii::error($category->errors, 'error-settler-nest-category-new-save');

        return $category;
    }

    public static function findChildrenAsArrayByCategorySourceId(int $categorySourceId): array
    {
        return self::find()
            ->select(['category.id as id', 'cs.id as csid', 'category.title', 'cs.source_url'])
            ->join('join', 'category_source cs', 'cs.category_id = category.id')
            ->where('cs.self_parent_id = ' . $categorySourceId)
            ->distinct(true)
            ->asArray()
            ->all();
    }

    public static function findAllAsArrayBySourceId(int $sourceId): array
    {
        return self::find()
            ->select(['category.id', 'category.title', 'cs.source_id as source'])
            ->join('join', 'category_source cs', 'cs.category_id = category.id')
            ->where('cs.source_id = ' . $sourceId)
            ->distinct(true)
            ->asArray()
            ->all();
    }

    public static function findAsArrayByCategorySourceId(int $categorySourceId)
    {
        return self::find()
            ->select('*')
            ->join('join', 'category_source cs', 'cs.category_id = category.id')
            ->where(['cs.id' => $categorySourceId])
            ->asArray()
            ->one();
    }

    public static function findAllAsArrayBySourceAndMaxNestLevel(int $sourceId, int $maxNest)
    {
        return Category::find()
            ->select(['category.id as id', 'cs.id as csid', 'category.title', 'cs.source_url'])
            ->join('join', 'category_source cs', 'cs.category_id = category.id')
            ->where(sprintf('cs.nest_level = %d AND cs.source_id = %d', $maxNest, $sourceId))
            ->distinct(true)
            ->asArray()
            ->all();
    }

    public static function findByTags(string $tags): ?self
    {
        return self::find()->where(['tags' => $tags])->one();
    }

    public static function findByCategorySourceId(int $categorySourceId)
    {
        $categorySource = CategorySource::findOne($categorySourceId);
        if (empty($categorySource))
            throw new NotFoundHttpException('CategorySource not found');
        
        return self::findOne($categorySource->category_id);
    }
}
