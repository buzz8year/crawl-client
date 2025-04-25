<?php

namespace crawler\models\product;

use crawler\models\attribute\Attribute;
use crawler\models\attribute\AttributeValue;
use Yii;

/**
 * This is the model class for table "product_attribute".
 *
 * @property int $product_id
 * @property int $attribute_id
 * @property int $attribute_value_id
 *
 * @property Product $product
 */
class ProductAttribute extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'product_attribute';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['product_id', 'attribute_id', 'attribute_value_id'], 'required'],
            [['product_id', 'attribute_id', 'attribute_value_id'], 'integer'],
            [['attribute_id'], 'exist', 'skipOnError' => true, 'targetClass' => Attribute::class, 'targetAttribute' => ['attribute_id' => 'id']],
            [['attribute_value_id'], 'exist', 'skipOnError' => true, 'targetClass' => AttributeValue::class, 'targetAttribute' => ['attribute_value_id' => 'id']],
            [['product_id'], 'exist', 'skipOnError' => true, 'targetClass' => Product::class, 'targetAttribute' => ['product_id' => 'id']],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'product_id' => 'Product ID',
            'attribute_id' => 'Attribute ID',
            'attribute_value_id' => 'Attribute Value ID',
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
    public function getAttributeValue()
    {
        return $this->hasOne(AttributeValue::class, ['id' => 'attribute_value_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getProduct()
    {
        return $this->hasOne(Product::class, ['id' => 'product_id']);
    }
}
