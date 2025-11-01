<?php

namespace crawler\models\keyword;

use crawler\models\source\Source;
use crawler\models\product\Product;
use yii\helpers\ArrayHelper;
use Yii;

class Keyword extends \yii\db\ActiveRecord
{
    public static function tableName()
    {
        return 'keyword';
    }

    public function rules()
    {
        return [
            [['word'], 'required'],
            [['word'], 'string', 'max' => 72],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'word' => 'Word',
        ];
    }

    public function getKeywordSources()
    {
        return $this->hasMany(KeywordSource::class, ['keyword_id' => 'id']);
    }

    public function getProducts()
    {
        return $this->hasMany(Product::class, ['source_word_id' => 'id']);
    }

    public static function getIdByWord(string $word)
    {
        return self::find()->where(['word' => $word])->one()->id ?? null;
    }

    public static function listKeywords() 
    {
        return ArrayHelper::map(self::find()->all(), 'id', 'word');
    }

    public static function findByWord(string $word)
    {
        return self::find()->where(['word' => $word])->one();
    }

    public static function findAllAsArrayBySourceId(int $sourceId)
    {
        return self::find()
            ->select(['keyword.id', 'keyword.word', 'ks.source_id as source'])
            ->join('join', 'keyword_source ks', 'ks.keyword_id = keyword.id')
            ->where('ks.source_id = ' . $sourceId)
            ->distinct(true)
            ->asArray()
            ->all();
    }

    public static function createByWord(string $word)
    {
        $keyword = new Keyword();
        $keyword->word = $word;
        $keyword->save();
        return $keyword;
    }

    public static function getOrCreate(int $sourceId, string $word, bool $global = false)
    {
        if ($found = self::findByWord($word)) 
        {
            if (!$keywordSource = KeywordSource::findByKeywordId($found->id)) 
                $keywordSource = KeywordSource::create($found->id, $sourceId);
        }
        else {
            $newKeyword = self::createByWord($word);
            $keywordSource = KeywordSource::create($newKeyword->id, $sourceId);

            // GLOBAL: Assign keyword globally, to all sources, or specifically to current one
            if ($global)
                KeywordSource::createForAllSources($newKeyword->id);
        }
        return Keyword::findOne($keywordSource->keyword_id)->word;
    }
}
