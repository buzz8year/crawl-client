<?php

namespace crawler\models\attribute;

use crawler\models\product\ProductAttribute;
use crawler\util\SettlingException;
use Yii;

/**
 * This is the model class for table "attribute_value".
 *
 * @property int $id
 * @property int $attribute_id
 * @property string $value
 *
 * @property Attribute $attribute0
 * @property ProductAttribute[] $productAttributes
 */
class AttributeValue extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'attribute_value';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['attribute_id', 'value'], 'required'],
            [['attribute_id'], 'integer'],
            [['value'], 'string'],
            [['attribute_id'], 'exist', 'skipOnError' => true, 'targetClass' => Attribute::className(), 'targetAttribute' => ['attribute_id' => 'id']],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'attribute_id' => 'Attribute ID',
            'value' => 'Value',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAttribute0()
    {
        return $this->hasOne(Attribute::class, ['id' => 'attribute_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getProductAttributes()
    {
        return $this->hasMany(ProductAttribute::class, ['attribute_value_id' => 'id']);
    }

    public static function findExisting(int $attributeId, string $value)
    {
        return self::find()->where(['attribute_id' => $attributeId, 'value' => trim($value)])->one();
    }

    public static function create(int $attributeId, string $value): AttributeValue
    {
        $model = new AttributeValue();
        $model->attribute_id = $attributeId;
        $model->value = $value;

        if (!$model->save()) 
            throw new SettlingException($model);

        return $model;
    }
}
