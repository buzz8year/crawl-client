<?php

namespace crawler\models\region;

use crawler\models\product\Product;
use yii\helpers\ArrayHelper;
use Yii;

/**
 * This is the model class for table "region".
 *
 * @property int $id
 * @property int $global
 * @property string $alias
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

    public function getProducts()
    {
        return $this->hasMany(Product::class, ['region_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRegionSources()
    {
        return $this->hasMany(RegionSource::class, ['region_id' => 'id']);
    }

    public static function findByRegionSource(RegionSource $regionSource)
    {
        return self::find()
            ->select('*')
            ->join('join', 'region_source rs', 'rs.region_id = region.id')
            ->where(['region.id' => $regionSource->region_id, 'rs.source_id' => $regionSource->source_id])
            ->asArray()
            ->one();
    }

    public static function findAllAsArrayByRegionSourceId(int $sourceId)
    {
        return self::find()
            ->join('join', 'region_source rs', 'rs.region_id = region.id')
            ->where(sprintf('rs.source_id = %d', $sourceId))
            ->distinct(true)
            ->asArray()
            ->all();
    }

    public static function listRegions()
    {
        return ArrayHelper::map(self::find()->all(), 'id', 'alias');
    }
}
