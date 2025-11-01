<?php

namespace crawler\models\attribute;

use crawler\models\product\ProductAttribute;
use yii\helpers\ArrayHelper;
use Yii;
use function DI\string;


class Attribute extends \yii\db\ActiveRecord
{

    public static function tableName()
    {
        return 'attribute';
    }

    public function rules()
    {
        return [
            [['title'], 'required'],
            [['title'], 'string', 'max' => 255],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'title' => 'Title',
        ];
    }

    public function getAttributeValues()
    {
        return $this->hasMany(AttributeValue::class, ['attribute_id' => 'id']);
    }

    public function getProductAttributes()
    {
        return $this->hasMany(ProductAttribute::class, ['attribute_id' => 'id']);
    }

    public static function listAttributes() 
    {
        return ArrayHelper::map( self::find()->all(), 'id', 'title' );
    }

    public static function findOneWithValues(int $id, int $valueId)
    {
        return self::find()
            ->select('*')
            ->join('LEFT JOIN', 'attribute_value av', 'av.attribute_id = attribute.id')
            ->where(['attribute.id' => $id, 'av.id' => $valueId])
            ->asArray()
            ->one();
    }

    public static function createByTitle(string $title)
    {
        $model = new self();
        $model->title = $title;
        if ($model->save())
            Yii::error('Attribute create error: ' . json_encode($model->getErrors()));
        return $model;
    }

    public static function findByTitle(string $title)
    {
        return self::find()->where(['title' => $title])->one();
    }
}
