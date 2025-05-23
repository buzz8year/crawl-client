<?php

namespace crawler\models\parser;

use crawler\models\attribute\Attribute;
use crawler\models\attribute\AttributeValue;

use crawler\models\category\Category;
use crawler\models\category\CategorySource;
use crawler\models\category\CategoryTags;

use crawler\models\history\History;
use crawler\models\description\Description;
use crawler\models\header\HeaderSource;
use crawler\models\header\HeaderValue;
use crawler\models\header\Header;
use crawler\models\image\Image;

use crawler\models\product\Product;
use crawler\models\product\ProductAttribute;
use crawler\models\product\ProductImage;

use crawler\models\proxy\ProxySource;
use crawler\models\proxy\Proxy;

use crawler\models\options\OptionsLog;
use crawler\models\morph\Morph;
use crawler\models\opencart\OcSettler;
use Yii;

// use crawler\models\SynonymaClient;

/**
 * ParserSettler processes parsed data and settles it properly down to the db.
 */
class ParserSettler implements ParserSettlingInterface
{
    public static $sourceId;
    public $factory;

    public function getDb(int $db) 
    {
        switch ($db) 
        {
            case 1: return Yii::$app->db;
            case 2: return Yii::$app->ocdb;
        }
    }

    /**
     * @var array grabs all missed categories, used to display immediately after tree is parsed.
     */
    public static $missedCategories = [
        'source' => [],
        'global' => [],
    ];

    public function __construct(ParserFactory $factory, int $sourceId = 1)
    {
        if ($sourceId)
            $this->setSourceId($sourceId);

        $this->factory = $factory;
    }

    /**
     * @param int $sourceId
     */
    public function setSourceId(int $sourceId)
    {
        self::$sourceId = $sourceId;
    }

    /**
     * @return array
     */
    public function syncProducts()
    {
        return OcSettler::saveProducts($this->factory->model->id);
    }

    /**
     * saveCategories() method initializes nestCategory() method for each category in array.
     * @param array $parsedCategories. The only rule for the array - if element has children, they must be caged within 'children' key
     * @param int $sourceId
     */
    public function saveCategories($parsedCategories, int $syncCategories = 0): void
    {
        if (isset(Yii::$app->session) && ($session = Yii::$app->session) && $session->isActive)
            $session->close();

        foreach ($parsedCategories as $category) {
            $this->nestCategory($category);
            print_r('PROCESSING: ' . $category->title . '. DONE' . PHP_EOL);
        }
        // if ($syncCategories)
        //     OcSettler::saveCategories(self::$sourceId);
    }

    /**
     * Recursively & properly settles the categories to the DB.
     * @param array $category - w/o children
     * @param int $parentId - null for the first iteration
     * @param int $nestLevel - 0 for the first iteration
     */
    public function nestCategory($category, int $parentId = null)
    {
        if (isset(Yii::$app->session) && ($session = Yii::$app->session) && $session->isActive)
            $session->close();

        $morphier = new Morph('ru');
        $lemmas = $morphier->getPhraseLemmas($category->title);

        $tags = [];
        foreach ($lemmas as $lemma) 
        {
            $tagExist = CategoryTags::find()->where(['tag' => $lemma])->one();
            if ($tagExist)
                $tagId = $tagExist->id;
            
            else {
                $newTag = new CategoryTags();
                $newTag->tag = $lemma;

                if ($newTag->save())
                    $tagId = $newTag->id;
            }
            $tags[] = $tagId;
        }
        natsort($tags);

        $implodeTags = implode('+', $tags);

        $categoryExist = Category::find()->where(['tags' => $implodeTags])->one();
        // $categoryExist = Category::find()->where(['title' => $category->title])->one();


        if ($categoryExist) 
        {
            if (!$categoryExist->tags) 
            {
                $categoryExist->tags = $implodeTags;
                $categoryExist->save();
            }
            // print_r('Exist ' . $categoryExist->title);
        } 
        else {
            $newCategory = new Category();
            $newCategory->tags  = $implodeTags;
            $newCategory->title = trim($category->title);
            // $newCategory->save();
            if (!$newCategory->save())
                print_r($newCategory->errors);
        }

        $categoryId = $categoryExist->id ?? $newCategory->id;

        if ($category->href) 
        {
            $categorySourceExist = CategorySource::find()
                ->where([
                    'source_id'   => self::$sourceId,
                    'source_url'  => $category->href,
                    // 'category_id' => $categoryId,
                ])
                ->one();
        } 
        else $categorySourceExist = false;

        if (!$categorySourceExist) 
        {
            $newCategorySource                        = new CategorySource();
            $newCategorySource->category_id           = $categoryId;
            $newCategorySource->source_url            = $category->href;
            $newCategorySource->source_category_alias = isset($category->alias) ? (string)$category->alias : '';
            $newCategorySource->source_category_id    = isset($category->csid) ? $category->csid : null;
            $newCategorySource->source_url_dump       = isset($category->dump) ? $category->dump : '';
            $newCategorySource->nest_level            = $category->nest_level;
            $newCategorySource->self_parent_id        = $parentId;
            $newCategorySource->source_id             = self::$sourceId;

            if (!$newCategorySource->save())
                print_r($newCategorySource->errors);
        }
        // else print_r('Exist ' . $categorySourceExist->id);

        $categorySourceId = $categorySourceExist->id ?? $newCategorySource->id;

        if (isset($category->children) && count($category->children))
            foreach ($category->children as $child)
                $this->nestCategory($child, $categorySourceId);
    }

    /**
     * Recursively checks for category existense & write misses to $missedCategories property.
     * @param array $category - w/o children
     * @param int $parentId - null for the first iteration
     * @param int $nestLevel - 0 for the first iteration
     */
    public function nestMisses($categories)
    {
        if (($session = Yii::$app->session) && $session->isActive)
            $session->close();

        foreach ($categories as $category) 
        {
            if (isset($category['title'])) 
            {
                $morphier = new Morph('ru');
                // print_r($category['title']);
                $lemmas = $morphier->getPhraseLemmas($category['title']);

                $tags = [];
                foreach ($lemmas as $lemma) 
                {
                    $tagExist = CategoryTags::find()->where(['tag' => $lemma])->one();
                    if ($tagExist)
                        $tags[] = $tagExist->id;
                }

                if ($tags) 
                {
                    natsort($tags);
                    $implodeTags = implode('+', $tags);
                    $categoryExist = Category::find()->where(['tags' => $implodeTags])->one();
                }


                if (isset($categoryExist) && $categoryExist) 
                {
                    $categorySourceExist = CategorySource::find()
                        ->where([
                            'source_id'   => self::$sourceId,
                            'source_url'  => $category['href'],
                            'category_id' => $categoryExist->id,
                        ])
                        ->one();

                    if (!$categorySourceExist) 
                        self::$missedCategories['source'][] = $category['title'];
                } 
                else {
                    self::$missedCategories['global'][] = $category['title'];
                    self::$missedCategories['source'][] = $category['title'];
                }

                if (isset($category['children']) && count($category['children']))
                    $this->nestMisses($category['children']);
            }


        }

        return self::$missedCategories;
    }

    /**
     * @inheritdoc
     * @param array $products
     * @param object $model
     * @return array - the data for the Details Parsing (decriptions. attributes, images) - [productId => productUrl]
     */
    public function saveProducts(array $products, $model)
    {
        // Yii::$app->session->addFlash('error', json_encode($products));
        $dataDetails = [];

        // Yii::$app->session->addFlash('success', count($products));

        foreach ($products as $key => &$product) 
        {
            if (isset($product['href'])) 
            {
                $productExist = Product::find()->where(['source_url' => $product['href']])->one();
                // $productExist = Product::find()->where(['title' => $product['name']])->one();

                // if (!$productExist || $productExist->source_url != $product['href']) {
                if (!$productExist) 
                {
                    $newProduct                     = new Product();
                    $newProduct->source_id          = $model->id;
                    $newProduct->category_id        = $model->categoryId;
                    $newProduct->category_source_id = $model->categorySourceId;
                    $newProduct->keyword_id         = $model->keywordId;
                    $newProduct->region_id          = $model->regionId;
                    $newProduct->source_url         = $product['href'];
                    $newProduct->price              = $product['price'];
                    $newProduct->title              = trim($product['name']);

                    // if (!$newProduct->save()) {
                    if (!$newProduct->save(false)) 
                    {
                        throw new \Exception(
                            'Error attempting to save product of URL: ' .
                            PHP_EOL . $product['href'] . PHP_EOL .
                            PHP_EOL . '$newProduct->source_id          = ' . $model->id .
                            PHP_EOL . '$newProduct->category_id        = ' . $model->categoryId .
                            PHP_EOL . '$newProduct->category_source_id = ' . $model->categorySourceId .
                            PHP_EOL . '$newProduct->keyword_id         = ' . $model->keywordId .
                            PHP_EOL . '$newProduct->region_id          = ' . $model->regionId .
                            PHP_EOL . '$newProduct->source_url         = ' . $product['href'] .
                            PHP_EOL . '$newProduct->title              = ' . $product['name'] .
                            PHP_EOL . '$newProduct->price              = ' . $product['price']
                        );
                    }
                    else {
                        if ((isset($product['descriptions']) && $product['descriptions'])
                            || (isset($product['attributes']) && $product['attributes'])
                            || (isset($product['images']) && $product['images'])) 
                        {
                                if (isset($product['descriptions']) && $product['descriptions'])
                                    $this->saveDescriptions($product['descriptions'], $newProduct->id);

                                if (isset($product['attributes']) && $product['attributes'])
                                    $this->saveAttributes($product['attributes'], $newProduct->id);

                                if (isset($product['images']) && $product['images'])
                                    $this->saveImages($product['images'], $newProduct->id);
                        }
                        else {
                            $url = (isset($product['api_href']) && $product['api_href']) ? $product['api_href'] : $product['href'];
                            $dataDetails[$newProduct->id] = $url;
                        }
                    }
                } 
                else {
                    if ($productExist->track_price) 
                    {
                        if ($productExist->price_new && $productExist->price_new != $product['price'])
                            $productExist->price = $productExist->price_new;

                        $productExist->price_new    = $product['price'];
                        $productExist->price_update = date('Y-m-d H:i:s', time());
                        $productExist->save();
                    }
                    // if ((isset($product['descriptions']) && $product['descriptions'])
                    //     || (isset($product['attributes']) && $product['attributes'])
                    //     || (isset($product['images']) && $product['images'])) {
                        
                    //         if (isset($product['descriptions']) && $product['descriptions']) {
                    //             $this->saveDescriptions($product['descriptions'], $productExist->id);
                    //         }
                    //         if (isset($product['attributes']) && $product['attributes']) {
                    //             $this->saveAttributes($product['attributes'], $productExist->id);
                    //         }
                    //         if (isset($product['images']) && $product['images']) {
                    //             $this->saveImages($product['images'], $productExist->id);
                    //         }
                    // }
                }
            }
        }

        return $dataDetails;
    }

    /**
     * @inheritdoc
     */
    public function saveDescriptions(array $descriptionData, int $productId, int $syncGoods = 0)
    {
        foreach ($descriptionData as $desc) 
        {
            if ($desc) 
            {
                if ($text = trim($desc['text'])) 
                {
                    $descExist = Description::find()->where(['product_id' => $productId, 'text_original' => $text])->one();

                    // $synonym = SynonymaClient::synonymize($desc['text'], $dictionaries);
                    if (!$descExist) 
                    {
                        $newDesc                = new Description();
                        $newDesc->title         = (isset($desc['title']) && $desc['title']) ? trim($desc['title']) : '';
                        $newDesc->product_id    = $productId;
                        $newDesc->text_original = $text;
                        // $newDesc->text_synonymized = $synonym;
                        // $newDesc->status = $synonym ? 1 : 0;

                        // $newDesc->save();
                        if (!$newDesc->save()) 
                        {
                            throw new \Exception('
                                Error attempting to save product image: ' .
                                PHP_EOL . '$newDesc->text_original        = ' . $newDesc->text_original .
                                PHP_EOL . '$newDesc->title   = ' . $newDesc->title .
                                PHP_EOL . '$newDesc->product_id = ' . $newDesc->product_id
                            );
                        }
                    }
                }
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function saveAttributes(array $attributeData, int $productId)
    {
        foreach ($attributeData as $attribute) 
        {
            if ($attribute && $attribute['title'] && $attribute['value']) 
            {
                $attributeExist = Attribute::find()->where(['title' => $attribute['title']])->one();

                if (!$attributeExist) 
                {
                    $newAttribute = new Attribute();
                    $newAttribute->title = trim($attribute['title']);
                    $newAttribute->save();
                }

                $valueExist = AttributeValue::find()->where([
                    'attribute_id' => $attributeExist ? $attributeExist->id : $newAttribute->id,
                    'value'        => trim($attribute['value']),
                ])->one();

                if (!$valueExist) 
                {
                    $newValue               = new AttributeValue();
                    $newValue->attribute_id = $attributeExist ? $attributeExist->id : $newAttribute->id;
                    $newValue->value        = trim($attribute['value']);

                    // $newValue->save();
                    if (!$newValue->save()) 
                    {
                        throw new \Exception('
                            Error attempting to save product image: ' .
                            PHP_EOL . '$newValue->attribute_id = ' . ($attributeExist ? $attributeExist->id : $newAttribute->id) .
                            PHP_EOL . '$newValue->value = ' . $newValue->value
                        );
                    }
                }

                $productAttributeExist = ProductAttribute::find()->where([
                    'product_id'         => $productId,
                    'attribute_id'       => $valueExist ? $valueExist->attribute_id : $newValue->attribute_id,
                    'attribute_value_id' => $valueExist ? $valueExist->id : $newValue->id,
                ])->one();

                if (!$productAttributeExist) 
                {
                    $newProductAttribute                     = new ProductAttribute();
                    $newProductAttribute->product_id         = $productId;
                    $newProductAttribute->attribute_id       = $attributeExist ? $attributeExist->id : $newAttribute->id;
                    $newProductAttribute->attribute_value_id = $valueExist ? $valueExist->id : $newValue->id;

                    // $newProductAttribute->save();
                    if (!$newProductAttribute->save()) 
                    {
                        throw new \Exception('
                            Error attempting to save product image: ' .
                            PHP_EOL . '$newProductAttribute->attribute_id        = ' . $newProductAttribute->attribute_id .
                            PHP_EOL . '$newProductAttribute->attribute_value_id   = ' . $newProductAttribute->attribute_value_id .
                            PHP_EOL . '$newProductAttribute->product_id = ' . $newProductAttribute->product_id
                        );
                    }
                }
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function saveImages(array $imageData, int $productId)
    {
        foreach ($imageData as $imageScope) 
        {
            if ($imageScope) 
            {
                foreach ($imageScope as $name => $image) 
                {
                    if ($name && $image) 
                    {
                        $imageExist = Image::find()->where(['source_url' => $image])->one();

                        if (!$imageExist) 
                        {
                            $newImage = new Image();
                            $newImage->source_url = $image;

                            if ($name != 'fullsize' && isset($fullsizeSavedId))
                                $newImage->self_parent_id = $fullsizeSavedId;

                            if (!$newImage->save()) 
                            {
                                throw new \Exception('
                                    Error attempting to save image (standalone): ' .
                                    PHP_EOL . '$newImage->source_url = ' . $image
                                );
                            }

                            if ($newImage->save() && $name == 'fullsize')
                                $fullsizeSavedId = $newImage->id;

                            $newProductImage             = new ProductImage();
                            $newProductImage->image_id   = $newImage->id;
                            $newProductImage->product_id = $productId;

                            // $newProductImage->save();
                            if (!$newProductImage->save()) 
                            {
                                throw new \Exception('
                                    Error attempting to save product image: ' .
                                    PHP_EOL . '$newImage->source_url        = ' . $image .
                                    PHP_EOL . '$newProductImage->image_id   = ' . $newImage->id .
                                    PHP_EOL . '$newProductImage->product_id = ' . $productId
                                );
                            }
                        } 
                        else {
                            $productImageExist = ProductImage::find()->where(['product_id' => $productId, 'image_id' => $imageExist->id])->one();

                            if (!$productImageExist) 
                            {
                                $newProductImage = new ProductImage();
                                $newProductImage->image_id = $imageExist->id;
                                $newProductImage->product_id = $productId;

                                // $newProductImage->save();
                                if (!$newProductImage->save()) 
                                {
                                    throw new \Exception('
                                        Error attempting to save product image: ' .
                                        PHP_EOL . '$newImage->source_url        = ' . $image .
                                        PHP_EOL . '$newProductImage->image_id   = ' . $newProductImage->id .
                                        PHP_EOL . '$newProductImage->product_id = ' . $productId
                                    );
                                }
                            }
                        }
                    }
                }
            }
        }
    }


    public static function logSession(int $status, string $url, int $sourceId, array $proxy = [], array $agent = [], string $client = null)
    {
        $history = new OptionsLog();

        $history->url       = $url;
        $history->source_id = $sourceId;
        $history->client    = $client;

        if ($proxy) 
        {
            $history->proxy_id = Proxy::find()
                ->where(['ip' => explode(':', $proxy['address'])[0], 'port' => explode(':', $proxy['address'])[1] ?? null])
                ->one()
                ->id;
        }

        if ($agent) 
        {
            $history->header_value_id = HeaderValue::find()
                ->where(['value' => explode(': ', $agent[0])[1]])
                ->one()
                ->id;
        }

        $history->status = $status;

        if (!$history->save()) 
        {
            throw new \Exception('Logging failed ' . 
                PHP_EOL . '$history->url = ' . $history->url .
                PHP_EOL . '$history->source_id = ' . $history->source_id .
                PHP_EOL . '$history->client = ' . $history->client .
                PHP_EOL . '$history->proxy_id = ' . $history->proxy_id .
                PHP_EOL . '$history->header_value_id = ' . $history->header_value_id .
                PHP_EOL . '$history->status = ' . $history->status
            );
        }
    }

    public static function logResults($model, string $time)
    {
        $history                     = new History();
        $history->url                = $model->url;
        $history->source_id          = $model->id;
        $history->category_source_id = $model->categorySourceId;
        $history->item_quantity      = count($model->products);
        $history->keyword_id         = $model->keywordId;
        $history->time               = $time;

        switch (true) 
        {
            case $model->warnings:
                $history->status = 5;
                $history->note   = History::STATUS_WARNING . implode(' - ', $model->warnings);

            case $history->item_quantity == 0:
                $history->status = 0;
                $history->note   = History::STATUS_ZERO;

            // case $parser->getMaxQuantity():
            //     if ($history->item_quantity >= $parser->getMaxQuantity()) {
            //         $history->status = 2;
            //         $history->note   = History::STATUS_MAX;
            //     }

            default:
                $history->status = 1;
        }
        $history->save();
    }

}
