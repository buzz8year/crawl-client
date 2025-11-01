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
use crawler\util\SettlingException;
use yii\base\DynamicModel;
use yz\shoppingcart\CartPositionInterface;
use yz\shoppingcart\CartPositionTrait;
use yii\db\ActiveQuery;


class Product extends \yii\db\ActiveRecord implements CartPositionInterface
{
    use CartPositionTrait;

    public const int SYNCED = 1;
    public const int NOT_SYNCED = 0;

    public const int TRACK_PRICE = 1;
    public const int NOT_TRACK_PRICE = 0;


    public static function tableName()
    {
        return 'product';
    }

    public function rules()
    {
        return [
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
            'price_update' => 'Price Last Update',
            'track_price' => 'Track Price',
            'sync_status' => 'Sync Status',
            'price_new' => 'Price New',
            'price' => 'Price',
        ];
    }

    public function rescheduledStatus()
    {
        return self::rescheduledStatusText()[$this->is_rescheduled];
    }

    public static function rescheduledStatusText()
    {
        return [
            self::SYNCED => 'Synced',
            self::NOT_SYNCED => 'Non-synced',
        ];
    }

    public function trackPriceStatus() {
        return self::trackPriceStatusText()[$this->track_price];
    }

    public static function trackPriceStatusText() 
    {
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
        if (isset($this->topCategorySource))
            return Category::findOne($this->topCategorySource->category_id);
    }

    public function getCategorySource()
    {   
        return $this->hasOne(CategorySource::class, ['id' => 'category_source_id']);
    }

    public function getTopCategorySource()
    {   
        if (isset($this->categorySource))
            return $this->findTopCategorySource($this->categorySource);
    }

    public function findTopCategorySource($categorySource)
    {   
        if (isset($categorySource->self_parent_id)) 
        {
            $parent = CategorySource::findOne($categorySource->self_parent_id);
            if ($parent->self_parent_id)
                return $this->findTopCategorySource($parent);

            return $parent;
        } 
        return $categorySource;
    }

    public function categoryTitle(): string
    {
        if (isset($this->category))
            return $this->category->category_title;

        return 'This category no longer exists.';
    }


    public function getSource(): ActiveQuery
    {
        return $this->hasOne(Source::class, ['id' => 'source_id']);
    }


    public function getKeyword(): ActiveQuery
    {
        return $this->hasOne(Keyword::class, ['id' => 'keyword_id']);
    }



    public function getDescriptions(): ActiveQuery
    {
        return $this->hasMany(Description::class, ['product_id' => 'id']);
    }


    public function getProductAttributes(): ActiveQuery
    {
        return $this->hasMany(ProductAttribute::class, ['product_id' => 'id']);
    }


    public function getAttributeValues(): array
    {
        $attributes = [];
        foreach ($this->productAttributes as $a) 
            if ($attribute = Attribute::findOneWithValues($a->attribute_id, $a->attribute_value_id)) 
                $attributes[] = ['title' => $attribute['title'], 'value' => $attribute['value']];

        return $attributes;
    }

    public function getProductImages()
    {
        return $this->hasMany(ProductImage::class, ['product_id' => 'id']);
    }

    public function getImages()
    {   
        $images = [];
        foreach ($this->productImages as $productImage) 
            if ($image = Image::findOneAsArray($productImage->image_id))
                $images[] = $image;

        return $images;
    }

    public function getThumb()
    {   
        return Image::findThumbAsArray($this->productImages[0]->id);
    }

    /**
     * @inheritdoc
     */
    public function getPrice()
    {
        return $this->price_new ?? $this->price;
    }

    /**
     * @inheritdoc
     */
    public function getId()
    {
        return $this->id;
    }

    public static function findOneByUrl($url)
    {
        return self::find()
            ->where(['source_url' => $url])
            ->one();
    }

    public static function createByParserModel(DynamicModel $model, array $data)
    {
        $product = new Product();
        $product->source_id = $model->id;
        $product->category_id = $model->categoryId;
        $product->category_source_id = $model->categorySourceId;
        $product->keyword_id = $model->keywordId;
        $product->region_id = $model->regionId;
        $product->source_url = $data['href'];
        $product->price = $data['price'];
        $product->title = trim($data['name']);

        if (!$product->save(false)) 
            throw new SettlingException($product);

        return $product;
    }
}
