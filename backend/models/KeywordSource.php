<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "keyword_source".
 *
 * @property int $id
 * @property int $keyword_id
 * @property int $source_id
 *
 * @property Keyword $keyword
 * @property Source $source
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
            [['keyword_id', 'source_id'], 'integer'],
            [['keyword_id'], 'exist', 'skipOnError' => true, 'targetClass' => Keyword::className(), 'targetAttribute' => ['keyword_id' => 'id']],
            [['source_id'], 'exist', 'skipOnError' => true, 'targetClass' => Source::className(), 'targetAttribute' => ['source_id' => 'id']],
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
        return $this->hasOne(Keyword::className(), ['id' => 'keyword_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSource()
    {
        return $this->hasOne(Source::className(), ['id' => 'source_id']);
    }
}
