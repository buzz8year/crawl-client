<?php

namespace backend\models\opencart;

use backend\models\parser\ParserProvisioner;
use backend\models\CategorySource;
use backend\models\Product;
use backend\models\Source;
use Yii;


class OcSettler
{
    /**
     * @return object, instance of class
     */
    public static function getDb()
    {
        return Yii::$app->ocdb;
    }



    /**
     * @return object
     */
    public static function getCategories()
    {
        $db = self::getDb();

        $categories = $db->createCommand('
            SELECT * 
            FROM oc_category c 
            LEFT JOIN oc_category_description cd ON(c.category_id = cd.category_id)
        ')->queryAll();

        return $categories;
    }



    /**
     * @return int
     */
    public static function countProducts()
    {
        $db = self::getDb();

        $data = $db->createCommand('
            SELECT COUNT(*) AS total 
            FROM oc_product_description
        ')->queryAll();

        return $data[0]['total'];
    }


    /**
     * @return array
     */
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





    /**
     * @return void
     */
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


    /**
     * @return
     */
    public static function deleteMisfits(int $sourceID = null)
    {
        $db = self::getDb();

        $productsYii = Product::find()->select('source_url')->asArray()->all();

        $productsOc = $db->createCommand('
            SELECT product_id, source_url
            FROM oc_product_description
        ')->queryAll();

        $data = [
            'total' => count($productsOc),
            'misfits' => 0,
        ];

        foreach ($productsOc as $key => $productOc) {
            if (!in_array($productOc['source_url'], array_column($productsYii, 'source_url'))) {
                $db->createCommand('DELETE FROM oc_product_description WHERE product_id = ' . $productOc['product_id'])->execute();
                $data['misfits']++;
            }
            if ($key % 100 == 0 || $productOc == end($productsOc)) {
                print_r($key . ' -> ');
            }
        }

        $db->createCommand('DELETE p FROM oc_product_description pd RIGHT JOIN oc_product p ON p.product_id = pd.product_id WHERE pd.product_id IS NULL')->execute();
        $db->createCommand('DELETE ps FROM oc_product_description pd RIGHT JOIN oc_product_to_store ps ON ps.product_id = pd.product_id WHERE pd.product_id IS NULL')->execute();
        $db->createCommand('DELETE pc FROM oc_product_description pd RIGHT JOIN oc_product_to_category pc ON pc.product_id = pd.product_id WHERE pd.product_id IS NULL')->execute();
        $db->createCommand('DELETE pa FROM oc_product_description pd RIGHT JOIN oc_product_attribute pa ON pa.product_id = pd.product_id WHERE pd.product_id IS NULL')->execute();
        $db->createCommand('DELETE pi FROM oc_product_description pd RIGHT JOIN oc_product_image pi ON pi.product_id = pd.product_id WHERE pd.product_id IS NULL')->execute();

        return $data;
    }


    /**
     * @return array, processed data info
     */
    public static function saveProducts(int $sourceId = null)
    {
        $db = self::getDb();

        // Yii::$app->db->pdo->setAttribute(\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);

        $sources = ParserProvisioner::activeSources();

        $data = [
            'processed' => 0,
            'updated' => 0,
            'synced' => 0,
        ];

        foreach ($sources as $srcID => $source) {
            try {
                if (!$sourceId || ($sourceId && $sourceId == $srcID)) {
                    // if ($srcID) {
                        self::saveCategories($srcID);
                        // $products = Product::find()->where(['source_id' => $srcID])->all();
                        // $products = Product::find()->where(['source_id' => $srcID, 'sync_status' => 0])->all();
                        $products = Product::find()->where(['source_id' => $srcID, 'sync_status' => 0])->batch();
                        // $products = Yii::$app->db->createCommand('
                        //     SELECT * 
                        //     FROM product 
                        //     WHERE sync_status = 0
                        //     AND source_id = ' . $srcID
                        // );
                        // $products = (new \yii\db\Query())->select('id')->from('product')->where(['sync_status' => 0, 'source_id' => $srcID]);
                    // } else {
                    //     self::saveCategories();
                    //     $products = Product::find()->all();
                    // }
                    // $source = Source::findOne($srcID);
                    // print_r('SYNC ' . $source->title . ' async products:' . PHP_EOL);
                    // print_r('SYNC ' . Source::findOne($srcID)->title . ' async products (' . count($products->all()) . '):' . PHP_EOL);
                    print_r('SYNC ' . Source::findOne($srcID)->title . ' async products (' . count($products) . '):' . PHP_EOL);

                    // foreach ($products->batch(1000) as $product) {
                    // foreach ($products->batch(1000) as $productID) {
                    // foreach ($source->products as $product) {
                    foreach ($products as $product) {
                        // $product = Product::findOne($productID);
                        $productExist = $db->createCommand('
                            SELECT * 
                            FROM oc_product_description 
                            WHERE source_url = ' . $db->quoteValue($product->source_url)
                        )->queryOne();

                        // if (!$productExist && $product->price) {
                        if (!$productExist && $product->price && ($product->productImages || $product->descriptions || $product->productAttributes)) {
                            $ocProductId = self::saveProduct($product);
                            $data['synced']++;
                        }

                        elseif ($productExist) {
                            $ocProductId = $productExist['product_id'];
                            self::updateProduct($product, $ocProductId);
                            $data['updated']++;
                        }

                        if (isset($ocProductId)) {
                            self::saveProductStore($ocProductId);
                            self::saveProductCategory($product, $ocProductId);
                            self::saveDescription($product, $ocProductId);
                            self::saveAttributes($product, $ocProductId);

                            $product->sync_status = 1;
                            $product->save();
                        }

                        $data['processed']++;

                        if ($data['processed'] % 100 == 0 || $product == end($products)) {
                            print_r($data['processed'] . ' -> ');
                        }
                    }

                    $usage = memory_get_peak_usage(true);
                    print_r('Peak: ' . round($usage / 1024 / 1024, 2) . ' MB' . PHP_EOL);
                }
            }

            catch (\Exception $e) {
                // echo $e->getMessage();
            }

        }



        // print_r($data);
        return $data;
    }




    /**
     * @return int
     */
    public static function saveProduct($product)
    {
        $db = self::getDb();

        $db->createCommand('
            INSERT INTO oc_product (image, price, date_added, status, quantity)
            VALUES (
                ' . $db->quoteValue($product->images ? $product->images[0]['source_url'] : '') . ',
                ' . $db->quoteValue($product->price_new ? $product->price_new : $product->price) . ',
                CURRENT_TIMESTAMP,
                1,
                1
            )'
        )->execute();

        return $db->getLastInsertID();
    }


    /**
     * @return int
     */
    public static function updateProduct($product, int $ocProductId)
    {
        $db = self::getDb();

        $db->createCommand('
            UPDATE oc_product
            SET price = ' . $db->quoteValue($product->price_new ? $product->price_new : $product->price) . ',
                image = ' . $db->quoteValue($product->images ? $product->images[0]['source_url'] : '') . ',
                date_modified = CURRENT_TIMESTAMP
            WHERE product_id = ' . $ocProductId
        )->execute();
    }



    /**
     * @return int
     */
    public static function saveProductStore(int $ocProductId)
    {
        $db = self::getDb();

        $productStoreExist = $db->createCommand('
            SELECT * 
            FROM oc_product_to_store 
            WHERE product_id = ' . $ocProductId
        )->queryOne();

        if (!$productStoreExist) {
            $db->createCommand('
                INSERT INTO oc_product_to_store (product_id, store_id) 
                VALUES (' . $ocProductId . ', 0)
            ')->execute();
        }
    }





    /**
     * @return void
     */
    public static function saveProductCategory($product, int $ocProductId)
    {
        if ($product->topCategory && $product->topCategory->category_outer_id) {
            $db = self::getDb();

            $productCategoryExist = $db->createCommand('
                SELECT * 
                FROM oc_product_to_category 
                WHERE product_id = ' . $ocProductId
            )->queryOne();

            if (!$productCategoryExist) {
                $db->createCommand('
                    INSERT INTO oc_product_to_category (product_id, category_id, main_category)
                    VALUES (
                        ' . $ocProductId . ',
                        ' . $product->topCategory->category_outer_id . ',
                        1
                    )'
                )->execute();
            } 

            // else {
            //     $db->createCommand('
            //         UPDATE oc_product_to_category
            //         SET product_id = ' . $ocProductId . ', category_id = ' . $product->topCategory->category_outer_id . ', main_category = 1
            //     ')->execute();
            // }
        }
    }





    /**
     * @return void
     */
    public static function saveDescription($product, int $ocProductId)
    {
        $db = self::getDb();

        $productDescExist = $db->createCommand('
            SELECT * 
            FROM oc_product_description 
            WHERE product_id = ' . $ocProductId
        )->queryOne();

        if (!$productDescExist) {
            $db->createCommand('
                INSERT INTO oc_product_description (product_id, name, description, tag, source_url, language_id)
                VALUES (
                    ' . $ocProductId . ',
                    ' . $db->quoteValue($product->title) . ',
                    ' . $db->quoteValue($product->descriptions ? $product->descriptions[0]->text_original : '') . ',
                    ' . $db->quoteValue($product->keyword ? $product->keyword->word : '') . ',
                    ' . $db->quoteValue($product->source_url) . ',
                    1
                )'
            )->execute();
        } 

        // else {
        //     $db->createCommand('
        //         UPDATE oc_product_description
        //         SET 
        //             description = ' . $db->quoteValue($product->descriptions ? $product->descriptions[0]->text_original : '') . ',
        //             product_id = ' . $ocProductId . ',
        //             source_url = ' . $db->quoteValue($product->source_url) . ',
        //             name = ' . $db->quoteValue($product->title) . ',
        //             tag = ' . $db->quoteValue($product->keyword ? $product->keyword->word : '') . ',
        //             language_id = 1
        //     ')->execute();
        // }
    }





    /**
     * @return void
     */ 
    public static function saveAttributes($product, int $ocProductId)
    {   
        $db = self::getDb();

        // $attrGroupExist = $db->createCommand('
        //     SELECT * 
        //     FROM oc_attribute_group 
        //     WHERE attribute_group_id = 0
        // ')->queryOne();

        // if (!$attrGroupExist) {
        //     $db->createCommand('
        //         INSERT INTO oc_attribute_group (attribute_group_id, sort_order)
        //         VALUES (
        //             0,
        //             0
        //         )'
        //     )->execute();
        //     $db->createCommand('
        //         INSERT INTO oc_attribute_group_description (name, attribute_group_id, language_id)
        //         VALUES (
        //             \'Общие\',
        //             0,
        //             1
        //         )'
        //     )->execute();
        // }

        if ($product->attributeValues) {
            foreach ($product->attributeValues as $attribute) {

                $attrExist = $db->createCommand('
                    SELECT * 
                    FROM oc_attribute_description 
                    WHERE name = ' . $db->quoteValue($attribute['title'])
                )->queryOne();

                if (!$attrExist) {

                    $db->createCommand('
                        INSERT INTO oc_attribute (attribute_group_id, sort_order)
                        VALUES (
                            0,
                            0
                        )
                    ')->execute();

                    $insAttrId = $db->getLastInsertID();

                    $db->createCommand('
                        INSERT INTO oc_attribute_description (attribute_id, name, language_id)
                        VALUES (
                            ' . $insAttrId . ',
                            ' . $db->quoteValue($attribute['title']) . ',
                            1
                        )
                    ')->execute();

                    $db->createCommand('
                        INSERT INTO oc_product_attribute (product_id, attribute_id, text, language_id)
                        VALUES (
                            ' . $ocProductId . ',
                            ' . $insAttrId . ',
                            ' . $db->quoteValue($attribute['value']) . ',
                            1
                        )
                    ')->execute();
                }

                else {
                    $prodAttrExist = $db->createCommand('
                        SELECT * 
                        FROM oc_product_attribute 
                        WHERE product_id = ' . $ocProductId . '
                        AND attribute_id = ' . $attrExist['attribute_id']
                    )->queryOne();

                    if (!$prodAttrExist) {
                        $db->createCommand('
                            INSERT INTO oc_product_attribute (product_id, attribute_id, text, language_id)
                            VALUES (
                                ' . $ocProductId . ',
                                ' . $attrExist['attribute_id'] . ',
                                ' . $db->quoteValue($attribute['value']) . ',
                                1
                            )
                        ')->execute();
                    }
                }
            }
        }
    }
}

