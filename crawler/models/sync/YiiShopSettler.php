<?php

namespace crawler\models\sync;

use crawler\models\parser\ParserProvisioner;
use crawler\models\CategorySource;
use crawler\models\Product;
use frontend\models\Product as YSProduct;
use crawler\models\source\Source;
use Yii;


class YiiShopSettler
{
	private $db;
    /**
     * Re-defining DB
     * @inheritdoc
     */
    public static function getDb() {
        return Yii::$app->ysdb;
    }

    /**
     * @return int
     */
    public static function saveProduct($product)
    {
        $ysProduct = new YSProduct();

        $ysProduct->title = $product->title;
        $ysProduct->price = $product->price_new ? $product->price_new : $product->price;
        $ysProduct->description = $product->descriptions ? $product->descriptions[0]->text_original : '';

        if ($ysProduct->save()) {
        	return true;
        }
        // $ysProduct->save();
    }

}