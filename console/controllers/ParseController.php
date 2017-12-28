<?php

namespace console\controllers;

use backend\models\parser\Parser;
use backend\models\parser\ParserProvisioner;
use backend\models\opencart\OcSettler;
use backend\models\Product;
use backend\models\Source;
use Yii;

class ParseController extends \yii\console\Controller
{
    public $src;
    public $sale;

    /**
     * Class instance properties
     * @return array
     */
    public function options($actionID)
    {
        $options = parent::options($actionID);
        if ($actionID == 'index') {
            $options[] = 'sale';
        }
        $options[] = 'src';
        return $options;
    }

    /**
     * Initiates Parsing (global or by source ID)
     * @return void
     */
    public function actionIndex()
    {
        if ($this->src) {
            $this->parseSource($this->src, $this->sale);
        } else {
            $provisioner = new ParserProvisioner();
            foreach ($provisioner->activeSources() as $sourceId => $source) {
                $this->parseSource($sourceId, $this->sale);
            }
        }
    }

    /**
     * Lists Sources IDs
     * @return void
     */
    public function actionList()
    {
        $sources = Source::find()->where(['status' => 1])->all();
        foreach ($sources as $source) {
            $this->stdout($source->id . PHP_EOL);
        }
    }

    /**
     * Lists Sources IDs and their Names
     * @return void
     */
    public function actionListName()
    {
        $sources = Source::find()->where(['status' => 1])->all();
        foreach ($sources as $source) {
            $this->stdout($source->id . ' - ' . $source->title . PHP_EOL);
        }
    }

    /**
     * Sync Products to OC
     * @return void
     */
    public function actionSync()
    {
        // Yii::info('SYNC ' . ($this->src ? Source::findOne($this->src)->title : '') . ' Products:' . PHP_EOL, 'parse-console');
        // $this->stdout('SYNC ' . ($this->src ? Source::findOne($this->src)->title : '') . ' Products:' . PHP_EOL);

        if ($syncData = OcSettler::saveProducts($this->src ?? null)) {
            Yii::info(
                'Processed: ' . $syncData['processed'] . PHP_EOL . 'Synced/Updated: ' . $syncData['synced'] . '/' . $syncData['updated'] . PHP_EOL,
                'parse-console'
            );

            $this->stdout(
                'Processed: ' . $syncData['processed'] . PHP_EOL .
                'Synced/Updated: ' . $syncData['synced'] . '/' . $syncData['updated'] . PHP_EOL
            );
        }
    }

    /**
     * Deletes all Misfit Products (those, not in Yii) from OC
     * @return void
     */
    public function actionDeleteMisfits()
    {
        if ($data = OcSettler::deleteMisfits()) {
            $this->stdout(
                'Total processed: ' . $data['total'] . PHP_EOL .
                'Misfits deleted: ' . $data['misfits'] . PHP_EOL
            );
            Yii::info(
                'DELETING MISFITS FROM OC' . PHP_EOL . 'Total processed: ' . $data['total'] . PHP_EOL . 'Misfits deleted: ' . $data['misfits'] . PHP_EOL,
                'parse-console'
            );
        }
    }




    /**
     * @return void
     */
    public function actionUpdateDetails()
    {
        if ($this->src) {
            $this->updateDetails($this->src);
        }
        else {
            $sources = Source::find()->where(['status' => 1])->all();
            foreach ($sources as $source) {
                $this->updateDetails($source->id);
            }
        }
    }



    /**
     * @return void
     */
    public function updateDetails(int $sourceID)
    {
        $super  = new Parser();
        $model  = $super->createModel($sourceID);
        $parser = new $model->class();

        $presentProducts = Source::findOne($model->id)->emptyProducts;
        // PRINT: Stdout console
        $this->stdout(
            PHP_EOL . PHP_EOL .
            'UPDATING DETAILS of all empty (' . count($presentProducts) . ') ' . $model->title . ' Products' .
            PHP_EOL
        );
        $parser->parseDetails($presentProducts);

        // PRINT: Stdout console
        $this->stdout('Done' . PHP_EOL);
        // LOG: console/runtime/logs/parse.log
        Yii::info('UPDATING DETAILS of all empty (' . count($presentProducts) . ') ' . $model->title . ' Products' . PHP_EOL . 'Done', 'parse-console');
    }



    /**
     * TEMP: Trim Letu.ru product urls
     * TODO: Remove
     * @return void
     */
    public function actionLetual()
    {
        $db = Yii::$app->db;
        $products = $db->createCommand('
            SELECT * 
            FROM product
            WHERE source_id = 26
        ')->queryAll();

        $urls = [];
        foreach ($products as $key => $product) {
            $exp = explode(';', $product['source_url']);

            if (!in_array($exp[0], $urls)) {
                $urls[] = $exp[0];
                $db->createCommand('
                    UPDATE product
                    SET source_url = ' . $db->quoteValue($exp[0]) . '
                    WHERE id = ' . $product['id']
                )->execute();
            } else {
                $db->createCommand('
                    DELETE FROM product
                    WHERE id = ' . $product['id']
                )->execute();
            }

            if ($key % 100 == 0 || $product == end($products)) {
                $this->stdout($key . ' -> ');
            }
        }
    }


    /**
     * Parses source by ID
     * @return void
     */
    public function parseSource(int $sourceId)
    {
        $super       = new Parser();
        $provisioner = new ParserProvisioner();

        $model  = $super->createModel($sourceId);
        $parser = new $model->class();

        $this->stdout(
            PHP_EOL . PHP_EOL .
            str_repeat('|', mb_strlen($model->title)) .
            PHP_EOL . strtoupper($model->title) .
            PHP_EOL . PHP_EOL
        );

        $categories = $provisioner->activeCategories($sourceId);
        $keywords   = $provisioner->listSourceKeywords($sourceId);

        $this->stdout('Categories Found: ' . count($categories) . PHP_EOL);
        // $this->stdout('Keywords Found: ' . count($keywords) . PHP_EOL . PHP_EOL);

        // LOG: console/runtime/logs/parse.log
        Yii::info('Categories to parse: ' . count($categories) . PHP_EOL, 'parse-console');
        Yii::info('Keywords to parse: ' . count($keywords) . PHP_EOL, 'parse-console');


        // SALE: Sale flag
        // if ($this->sale === true) {

            if ($this->sale === true) {
                $model->saleFlag = true;
            }

            if (method_exists($model->class, 'xpathSale')) {
                if ($categories) {
                    $this->stdout(PHP_EOL . PHP_EOL . 'ITERATE: Categories' . PHP_EOL . PHP_EOL);
                    foreach ($categories as $key => $category) {

                        $model->categorySourceId = $category['csid'];
                        $model->categoryId       = $category['id'];

                        $model->url = $parser->urlBuild('', $category['csid'], '');

                        if ($productsReturn = $parser->parseProducts()) {
                            $this->stdout($model->url . PHP_EOL . $productsReturn . ' Products' . PHP_EOL);
                            Yii::info($model->url . PHP_EOL . $productsReturn . ' Products' . PHP_EOL, 'parse-console');
                        }
                        if ($detailsReturn = $parser->parseDetails()) {
                            // $parser->syncProducts();

                            $this->stdout($detailsReturn . ' Detailed' . PHP_EOL . PHP_EOL);
                            Yii::info($detailsReturn . ' Detailed' . PHP_EOL . PHP_EOL, 'parse-console');
                        } else {
                            $this->stdout('.' . PHP_EOL);
                        }
                    }
                }
            } 

            else {
                if ($keywords) {
                    $this->stdout('ITERATE: Keywords' . PHP_EOL . PHP_EOL);

                    foreach ($keywords as $keywordId => $keyword) {
                        $model->keywordId = $keywordId;
                        $model->url       = $parser->urlBuild('', '', $keyword);

                        if ($productsReturn = $parser->parseProducts()) {
                            $this->stdout(
                                $model->url . PHP_EOL .
                                $productsReturn . ' Products' . PHP_EOL
                            );
                            Yii::info($model->url . PHP_EOL . $productsReturn . ' Products' . PHP_EOL, 'parse-console');
                        }
                        if ($detailsReturn = $parser->parseDetails()) {
                            // $parser->syncProducts();

                            $this->stdout($detailsReturn . ' Detailed' . PHP_EOL . PHP_EOL);
                            Yii::info($detailsReturn . ' Detailed' . PHP_EOL . PHP_EOL, 'parse-console');
                        } else {
                            $this->stdout('.' . PHP_EOL);
                        }
                    }
                }
            }
        // }
    }
}
