<?php

namespace crawler\models\category;

use Yii;

/**
 * This is the model class for table "category_tags".
 *
 * @property int $id
 * @property string $tags
 * @property int $status
 *
 * @property CategorySource[] $categorySources
 */
class CategoryTags extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'category_tags';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['tag'], 'required'],
            [['status'], 'integer'],
            [['tag'], 'string', 'max' => 64],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'tag' => 'Tag',
            'status' => 'Status',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCategorySources()
    {
        return $this->hasMany(CategorySource::className(), ['category_tags_id' => 'id']);
    }
}
