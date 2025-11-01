<?php

namespace crawler\parser;

use crawler\models\attribute\Attribute;
use crawler\models\attribute\AttributeValue;

use crawler\models\category\Category;
use crawler\models\category\CategorySource;
use crawler\models\category\CategoryTags;

use crawler\models\description\Description;
use crawler\models\header\HeaderValue;
use crawler\models\image\Image;

use crawler\parser\interface\ParserSettlingInterface;
use crawler\models\product\ProductAttribute;
use crawler\models\product\ProductImage;
use crawler\models\product\Product;

use crawler\models\proxy\Proxy;
use crawler\models\options\OptionsLog;
use crawler\models\sync\OcSettler;

use crawler\models\morph\Morph;
// use crawler\models\SynonymaClient;

use crawler\util\SettlingException;

use Yii;


/**
 * ParserSettler processes parsed data and settles it properly down to the db.
 */
class ParserSettler implements ParserSettlingInterface
{
    private $factory;

    // NOTE: grabs all missed categories, used to display immediately after tree is parsed.
    private $missedCategories = [
        'source' => [],
        'global' => [],
    ];

    public function __construct(ParserFactory $factory)
    {
        $this->factory = $factory;
    }

    public function getDb(int $db) 
    {
        return match ($db) 
        {
            1 => Yii::$app->db,
            2 => Yii::$app->ocdb,
        };
    }

    public function isAnyDetailSet($product)
    {
        return (isset($product['descriptions']) && $product['descriptions'])
            || (isset($product['attributes']) && $product['attributes'])
            || (isset($product['images']) && $product['images']);
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

        foreach ($parsedCategories as $category) 
        {
            $this->nestCategory($category);
            printf("PROCESSING: %s. DONE\n", $category->title);
        }
        // if ($syncCategories)
        //     OcSettler::saveCategories(self::$sourceId);
    }


    /**
     * @inheritdoc
     * @param array $products
     * @param object $model
     * @return array - the data for the Details Parsing (decriptions. attributes, images) - [productId => productUrl]
     */
    public function saveProducts(array $products)
    {
        $dataDetails = [];
        foreach ($products as $product) 
        {
            if (empty($product['href'])) 
                continue;

            $productExist = Product::findOneByUrl($product['href']);
            if (!$productExist) 
            {
                $newProduct = Product::createByParserModel($this->factory->model, $product);

                if ($this->isAnyDetailSet($product)) 
                    $this->saveDetails($product, $newProduct->id);

                else $url = (isset($product['api_href']) && $product['api_href']) 
                    ? $product['api_href'] 
                    : $product['href'];

                $dataDetails[$newProduct->id] = $url;
            } 
            elseif ($productExist->track_price) 
            {
                if ($productExist->price_new && $productExist->price_new != $product['price'])
                    $productExist->price = $productExist->price_new;

                $productExist->price_new = $product['price'];
                $productExist->price_update = date('Y-m-d H:i:s', time());
                $productExist->save();
            }
        }
        return $dataDetails;
    }


    public function saveDetails(array $product, int $productId)
    {
        if (isset($product['descriptions']) && $product['descriptions'])
            $this->saveDescriptions($product['descriptions'], $productId);

        if (isset($product['attributes']) && $product['attributes'])
            $this->saveAttributes($product['attributes'], $productId);

        if (isset($product['images']) && $product['images'])
            $this->saveImages($product['images'], $productId);
    }

    /**
     * @inheritdoc
     */
    public function saveDescriptions(array $descriptionData, int $productId, int $syncGoods = 0)
    {
        foreach ($descriptionData as $desc) 
        {
            if (empty($desc['text']))
                continue;
            
            $text = trim($desc['text']);
            if (Description::exists($productId, $text)) 
                continue;

            Description::create($productId, $text,
                !empty($desc['title']) ? trim($desc['title']) : '');
        }
    }

    /**
     * @inheritdoc
     */
    public function saveAttributes(array $attributeData, int $productId)
    {
        foreach ($attributeData as $attribute) 
        {
            if (!($attribute && $attribute['title'] && $attribute['value'])) 
                continue;

            if (!$attributeExist = Attribute::findByTitle($attribute['title'])) 
                $newAttribute = Attribute::createByTitle($attribute['title']);

            $attributeId = $attributeExist ? $attributeExist->id : $newAttribute->id;
            $valueExist = AttributeValue::findExisting($attributeId, $attribute['value']);

            if (!$valueExist) 
                $newValue = AttributeValue::create($attributeId, $attribute['value']);

            $valueId = $valueExist ? $valueExist->id : $newValue->id;
            $attributeId = $valueExist ? $valueExist->attribute_id : $newValue->attribute_id;

            $productAttributeExist = ProductAttribute::findExisting($productId, $attributeId, $valueId);
            if ($productAttributeExist) 
                continue;

            $attributeId = $attributeExist ? $attributeExist->id : $newAttribute->id;
            ProductAttribute::create($productId, $attributeId, $valueId);
        }
    }

    /**
     * @inheritdoc
     */
    public function saveImages(array $imageData, int $productId): void
    {
        foreach ($imageData as $imageScope) 
        {
            if (empty($imageScope)) 
                continue;

            foreach ($imageScope as $name => $image) 
            {
                if (!($name && $image)) 
                    continue;

                if (!$imageExist = Image::findOneByUrl($image)) 
                {
                    $newImage = new Image();
                    $newImage->source_url = $image;

                    if ($name != 'fullsize' && isset($fullsizeSavedId))
                        $newImage->self_parent_id = $fullsizeSavedId;

                    if (!$newImage->save()) 
                        throw new SettlingException($newImage);

                    if ($newImage->save() && $name == 'fullsize')
                        $fullsizeSavedId = $newImage->id;

                    ProductImage::createByImageIdAndProductId($newImage->id, $productId);
                } 
                elseif (!ProductImage::findExisting($productId, $imageExist->id)) 
                    ProductImage::createByImageIdAndProductId($imageExist->id, $productId);
            }
        }
    }


    /**
     * Recursively & properly settles the categories to the DB.
     * @param array $category - w/o children
     * @param int $parentId - null for the first iteration
     * @param int $nestLevel - 0 for the first iteration
     */
    public function nestCategory(array $category, int $parentId = null)
    {
        $lemmas = new Morph('ru')->getPhraseLemmas($category['title']);
        $tags = [];

        foreach ($lemmas as $lemma) 
        {
            if ($tagExist = CategoryTags::findByTag($lemma))
                $tagId = $tagExist->id;
            
            elseif ($newTag = CategoryTags::createByTag($lemma))
                $tagId = $newTag->id;

            $tags[] = $tagId;
        }
        natsort($tags);

        $implodeTags = implode('+', $tags);
        $categoryExist = Category::findByTags($implodeTags);

        if (empty($categoryExist)) 
            $newCategory = Category::createByTagsAndTitle($implodeTags, $category['title']);

        elseif (empty($categoryExist->tags)) 
            $categoryExist->saveTags($implodeTags);

        $categoryId = $categoryExist->id ?? $newCategory->id;

        $categorySourceExist = CategorySource::findBySourceIdAndUrl($this->factory->source->id, $category['href']);
        if (empty($categorySourceExist)) 
            $newCategorySource = CategorySource::createByCategoryArray($category, $categoryId, $parentId, $this->factory->source->id);

        $categorySourceId = $categorySourceExist->id ?? $newCategorySource->id;

        if (!empty($category['children']))
            foreach ($category['children'] as $child)
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
            if (empty($category['title'])) 
                continue;
            
            $morphier = new Morph('ru');
            $lemmas = $morphier->getPhraseLemmas($category['title']);

            $tags = [];
            foreach ($lemmas as $lemma) 
                if ($tagExist = CategoryTags::findByTag($lemma))
                    $tags[] = $tagExist->id;

            if ($tags) 
            {
                natsort($tags);
                $implodeTags = implode('+', $tags);
                $categoryExist = Category::find()->where(['tags' => $implodeTags])->one();
            }

            if (isset($categoryExist) && $categoryExist) 
            {
                $categorySourceExist = CategorySource::findExisting($categoryExist->id, $this->factory->source->id, $category['href']);
                if (!$categorySourceExist) 
                    $this->missedCategories['source'][] = $category['title'];
            } 
            else {
                $this->missedCategories['global'][] = $category['title'];
                $this->missedCategories['source'][] = $category['title'];
            }

            if (isset($category['children']) && count($category['children']))
                $this->nestMisses($category['children']);
        }
        return $this->missedCategories;
    }


    public static function logSession(int $status, string $url, int $sourceId, array $proxy = [], array $agent = [], string $client = null)
    {
        $history = new OptionsLog();
        $history->source_id = $sourceId;
        $history->client = $client;
        $history->status = $status;
        $history->url = $url;

        if (!empty($proxy['address'])) 
            $history->proxy_id = Proxy::getIdByAddress($proxy['address']);

        if (!empty($agent)) 
            $history->header_value_id = HeaderValue::getIdByAgent($agent[0]);

        if (!$history->save()) 
            throw new SettlingException($history);
    }

}
