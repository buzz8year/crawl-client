<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "region".
 *
 * @property int $id
 * @property string $alias
 * @property int $global
 *
 * @property RegionSource[] $regionSources
 */
class Region extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'region';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['alias'], 'required'],
            [['global'], 'integer'],
            [['alias'], 'string', 'max' => 32],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'alias' => 'Alias',
            'global' => 'Global',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRegionSources()
    {
        return $this->hasMany(RegionSource::className(), ['region_id' => 'id']);
    }

    public function getProducts()
    {
        return $this->hasMany(Products::className(), ['region_id' => 'id']);
    }
}
