<?php

namespace backend\models\opencart;

use backend\models\CategorySource;
use backend\models\Product;
use Yii;

class OcSettler
{
    public static function getDb()
    {
        return Yii::$app->ocdb;
    }

    public static function getCategories()
    {
        $db         = self::getDb();
        $categories = $db->createCommand('SELECT * FROM oc_category c LEFT JOIN oc_category_description cd ON(c.category_id = cd.category_id)')->queryAll();
        return $categories;
    }

    public static function saveCategories(int $sourceId = null)
    {
        $db = self::getDb();

        $data = [
        	'processed' => 0,
        	'synced' => [],
        ];

        if ($sourceId) {
            $categories = CategorySource::find()->where(['source_id' => $sourceId, 'nest_level' => 0])->all();
        } else {
            $categories = CategorySource::find()->where(['nest_level' => 0])->all();
        }

        foreach ($categories as $category) {
            $tags          = implode('+', $category->tags);
            $categoryExist = $db->createCommand('SELECT * FROM oc_category_description WHERE meta_keyword = ' . $db->quoteValue($tags))->queryOne();

            if (!in_array($category, $data['synced']) && !$categoryExist) {
                $db->createCommand('
                	INSERT INTO oc_category (status) 
                	VALUES (1)'
                )->execute();

                $outer_id = $db->getLastInsertID();

                $db->createCommand('
                	INSERT INTO oc_category_description (category_id, name, meta_keyword, language_id) 
                	VALUES (
                		' . $outer_id . ', 
                		' . $db->quoteValue($category->category->title) . ', 
                		' . $db->quoteValue($tags) . ', 
                		1
                	)'
            	)->execute();

                $db->createCommand('
                	INSERT INTO oc_category_to_store (category_id, store_id) 
	                VALUES ("' . $outer_id . '", 0)'
	            )->execute();

                $db->createCommand('
                	INSERT INTO oc_category_to_layout (category_id, layout_id) 
	                VALUES ("' . $outer_id . '", 0)'
	            )->execute();

                $category->category->category_outer_id = $outer_id;
                $category->category->save();

                $data['synced'][] = $tags;
            }

            $data['processed']++;
        }

        return $data;
    }

    // public static function saveCategory($category)
    // {
    //     $db            = self::getDb();
    //     $tags          = implode('+', $category->tags);
    //     $categoryExist = $db->createCommand('SELECT * FROM oc_category_description WHERE meta_keyword = \'' . $tags . '\'')->queryOne();

    //     if (!$categoryExist) {
    //         $db->createCommand('INSERT INTO oc_category (status) VALUES (1)')->execute();
    //         $outer_id = $db->getLastInsertID();

    //         $db->createCommand('INSERT INTO oc_category_description (category_id, name, meta_keyword, language_id) VALUES (\'' . $outer_id . '\', \'' . $category->category->title . '\', \'' . $tags . '\', 1)')->execute();
    //         $db->createCommand('INSERT INTO oc_category_to_store (category_id, store_id) VALUES (\'' . $outer_id . '\', 0)')->execute();

    //         $category->category->category_outer_id = $outer_id;
    //         $category->category->save();
    //     }
    // }

    public static function saveProducts(int $sourceId = null)
    {
        $db = self::getDb();

        if ($sourceId) {
            $products = Product::find()->where(['source_id' => $sourceId])->all();
            // $products = Product::find()->where(['source_id' => $sourceId, 'sync_status' => 0])->all();
        } else {
            $products = Product::find()->all();
            // $products = Product::find()->where(['sync_status' => 0])->all();
        }

        $data = [
        	'processed' => 0,
        	'synced' => 0,
        ];

        foreach ($products as $product) {
            $productExist = $db->createCommand('SELECT * FROM oc_product_description WHERE source_url = ' . $db->quoteValue($product->source_url))->queryOne();

            if (!$productExist) {
                $db->createCommand('
	            	INSERT INTO oc_product (image, price, status, quantity)
		            VALUES (
			            ' . $db->quoteValue($product->images ? $product->images[0]['source_url'] : '') . ',
			            ' . $db->quoteValue($product->price_new ? $product->price_new : $product->price) . ',
			            1,
			            1
			        )'
                )->execute();

                $insertedId = $db->getLastInsertID();

                $db->createCommand('
	            	INSERT INTO oc_product_description (product_id, name, description, tag, source_url, language_id)
	            	VALUES (
	            		' . $insertedId . ',
	            		' . $db->quoteValue($product->title) . ',
                        ' . $db->quoteValue($product->descriptions ? $product->descriptions[0]->text_original : '') . ',
                        ' . $db->quoteValue($product->keyword ? $product->keyword->word : '') . ',
	            		' . $db->quoteValue($product->source_url) . ',
	            		1
	            	)'
                )->execute();

                if ($product->topCategory) {
                    $db->createCommand('
		            	INSERT INTO oc_product_to_category (product_id, category_id, main_category)
		            	VALUES (
		            		' . $insertedId . ',
		            		' . $product->topCategory->category_outer_id . ',
		            		1
		            	)'
                    )->execute();
                }

                $db->createCommand('INSERT INTO oc_product_to_store (product_id, store_id) VALUES ("' . $insertedId . '", 0)')->execute();

                $product->sync_status = 1;
                $product->save();

                $data['synced']++;

            } else {
                $productCategoryExist = $db->createCommand('SELECT * FROM oc_product_to_category WHERE product_id = ' . $productExist['product_id'])->queryOne();
                
                if (!$productCategoryExist) {
                    if ($product->topCategory || $product->topCategory) {
                        $db->createCommand('
                            INSERT INTO oc_product_to_category (product_id, category_id, main_category)
                            VALUES (
                                ' . $productExist['product_id'] . ',
                                ' . $product->topCategory->category_outer_id . ',
                                1
                            )'
                        )->execute();

                        $data['synced']++;
                    }
                }
            }

            $data['processed']++;
        }

        // print_r($data);
        return $data;
    }
}
