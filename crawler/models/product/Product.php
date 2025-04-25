<?php

namespace crawler\models\product;

use crawler\models\attribute\Attribute;
use crawler\models\category\Category;
use crawler\models\category\CategorySource;
use crawler\models\description\Description;
use crawler\models\image\Image;
use crawler\models\keyword\Keyword;
use crawler\models\region\Region;
use crawler\models\source\Source;
use yii\behaviors\SluggableBehavior;
use yz\shoppingcart\CartPositionInterface;
use yz\shoppingcart\CartPositionTrait;


class Product extends \yii\db\ActiveRecord implements CartPositionInterface
{
    use CartPositionTrait;

    const SYNCED = 1;
    const NOT_SYNCED = 0;

    const TRACK_PRICE = 1;
    const NOT_TRACK_PRICE = 0;


    public static function tableName()
    {
        return 'product';
    }


    public function rules()
    {
        return [
            // [['source_id', 'keyword_id', 'category_id', 'source_url', 'price', 'price_new', 'price_new_last_update'], 'required'],
            [['source_url'], 'required'],
            [['source_id', 'keyword_id', 'category_id', 'category_source_id', 'region_id', 'track_price', 'sync_status'], 'integer'],
            [['source_url', 'title'], 'string'],
            [['price', 'price_new'], 'number'],
            [['price_update', 'date_created'], 'safe'],
            [['source_id'], 'exist', 'skipOnError' => true, 'targetClass' => Source::class, 'targetAttribute' => ['source_id' => 'id']],
            [['category_id'], 'exist', 'skipOnError' => true, 'targetClass' => Category::class, 'targetAttribute' => ['category_id' => 'id']],
            [['keyword_id'], 'exist', 'skipOnError' => true, 'targetClass' => Keyword::class, 'targetAttribute' => ['keyword_id' => 'id']],
            [['region_id'], 'exist', 'skipOnError' => true, 'targetClass' => Region::class, 'targetAttribute' => ['region_id' => 'id']],
        ];
    }


    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'source_id' => 'Source',
            'keyword_id' => 'Keyword',
            'category_id' => 'Category',
            'source_url' => 'Source Url',
            'price' => 'Price',
            'price_new' => 'Price New',
            'price_update' => 'Price Last Update',
            'track_price' => 'Track Price',
            'sync_status' => 'Sync Status',
        ];
    }


    

    public function rescheduledStatus()
    {
        return self::rescheduledStatusText()[$this->is_rescheduled];
    }

    public static function rescheduledStatusText()
    {
        return [
            self::SYNCED     => 'Synced',
            self::NOT_SYNCED => 'Non-synced',
        ];
    }

    public function trackPriceStatus() {
        return self::trackPriceStatusText()[$this->track_price];
    }

    public static function trackPriceStatusText() {
        return [
            self::TRACK_PRICE => 'Track',
            self::NOT_TRACK_PRICE => 'Do not track',
        ];
    }



    public function getCategory()
    {   
        return $this->hasOne(Category::class, ['id' => 'category_id']);
    }

    public function getTopCategory()
    {   
        if (isset($this->topCategorySource)) {
            return Category::findOne($this->topCategorySource->category_id);
        }
    }

    public function getCategorySource()
    {   
        return $this->hasOne(CategorySource::class, ['id' => 'category_source_id']);
    }

    public function getTopCategorySource()
    {   
        // if (isset($this->categorySource) && $this->categorySource->self_parent_id) {
        if (isset($this->categorySource)) {
            return $this->findTopCategorySource($this->categorySource);
        }
    }

    public function findTopCategorySource($categorySource)
    {   
        if (isset($categorySource->self_parent_id)) {
            $parent = CategorySource::findOne($categorySource->self_parent_id);
            if ($parent->self_parent_id) {
                return $this->findTopCategorySource($parent);
            }
            return $parent;
        } 
        else {
            return $categorySource;
        }
    }

    public function categoryTitle()
    {
        if (isset($this->category)) {
            return $this->category->category_title;
        }

        return 'Такая категория больше не существует';
    }


    public function getSource()
    {
        return $this->hasOne(Source::class, ['id' => 'source_id']);
    }


    public function getKeyword()
    {
        return $this->hasOne(Keyword::class, ['id' => 'keyword_id']);
    }



    public function getDescriptions()
    {
        return $this->hasMany(Description::class, ['product_id' => 'id']);
    }


    public function getProductAttributes()
    {
        return $this->hasMany(ProductAttribute::class, ['product_id' => 'id']);
    }


    public function getAttributeValues()
    {
        $attributes = [];
        foreach ($this->productAttributes as $productAttribute) {
            $attribute = Attribute::find()->select('*')->join('LEFT JOIN', 'attribute_value av', 'av.attribute_id = attribute.id')->where(['attribute.id' => $productAttribute->attribute_id, 'av.id' => $productAttribute->attribute_value_id])->asArray()->one();
            if ($attribute) {
                $attributes[] = [
                    'title' => $attribute['title'],
                    'value' => $attribute['value'],
                ];
            }
        }
        return $attributes;
    }


    public function getProductImages()
    {
        return $this->hasMany(ProductImage::class, ['product_id' => 'id']);
    }

    public function getImages()
    {   
        $images = [];
        foreach ($this->productImages as $productImage) {
            $image = Image::find()->where(['id' => $productImage->image_id])->asArray()->one();
            if ($image) {
                $images[] = $image;
            }
        }
        return $images;
    }

    public function getThumb()
    {   
        $image = Image::find()->where(['id' => $this->productImages[0]->id])->andWhere(['not', ['self_parent_id' => null]])->asArray()->one();
        if ($image) {
            return $image;
        }
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    // public function getOrderItems()
    // {
    //     return $this->hasMany(OrderItem::class, ['product_id' => 'id']);
    // }

    /**
     * @inheritdoc
     */
    public function getPrice()
    {
        return $this->price_new ? $this->price_new : $this->price;
    }

    /**
     * @inheritdoc
     */
    public function getId()
    {
        return $this->id;
    }
}
