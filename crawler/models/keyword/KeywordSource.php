<?php

namespace crawler\models\keyword;

use crawler\models\source\Source;
use Yii;

/**
 * This is the model class for table "keyword_source".
 *
 * @property int $id
 * @property int $keyword_id
 * @property int $source_id
 *
 * @property Keyword $keyword
 * @property $source
 */
class KeywordSource extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'keyword_source';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['keyword_id', 'source_id'], 'required'],
            [['keyword_id', 'source_id', 'category_source_id'], 'integer'],
            [['category_source_id'], 'default', 'value'=> 0],
            // [['category_source_id'], 'exist', 'skipOnError' => true, 'targetClass' => Keyword::class, 'targetAttribute' => ['keyword_id' => 'id']],
            [['keyword_id'], 'exist', 'skipOnError' => true, 'targetClass' => Keyword::class, 'targetAttribute' => ['keyword_id' => 'id']],
            [['source_id'], 'exist', 'skipOnError' => true, 'targetClass' => Source::class, 'targetAttribute' => ['source_id' => 'id']],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'keyword_id' => 'Keyword ID',
            'source_id' => 'Source ID',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getKeyword()
    {
        return $this->hasOne(Keyword::class, ['id' => 'keyword_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSource()
    {
        return $this->hasOne(Source::class, ['id' => 'source_id']);
    }

    public static function create(int $keywordId, int $sourceId)
    {
        $keywordSource = new self();
        $keywordSource->keyword_id = $keywordId;
        $keywordSource->source_id = $sourceId;
        $keywordSource->save();

        return $keywordSource;
    }
}
