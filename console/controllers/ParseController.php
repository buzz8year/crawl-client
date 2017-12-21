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
    public $sale = false;

    public function options($actionID)
    {
        $options = parent::options($actionID);
        if ($actionID == 'index') {
            $options[] = 'src';
            $options[] = 'sale';
        }
        if ($actionID == 'sync') {
            $options[] = 'src';
        }
        return $options;
    }

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

    public function actionList()
    {
        $sources = Source::find()->where(['status' => 1])->all();
        foreach ($sources as $source) {
            $this->stdout($source->id . PHP_EOL);
        }
    }

    public function actionListName()
    {
        $sources = Source::find()->where(['status' => 1])->all();
        foreach ($sources as $source) {
            $this->stdout($source->id . ' - ' . $source->title . PHP_EOL);
        }
    }

    public function actionSync()
    {
        Yii::info('SYNC Goods: ' . PHP_EOL, 'parse-console');
        $this->stdout('SYNC Goods: ' . PHP_EOL);

        if ($syncData = OcSettler::saveProducts($this->src ?? null)) {
            Yii::info('Processed: ' . $syncData['processed'] . PHP_EOL, 'parse-console');
            Yii::info('Synced/Updated: ' . $syncData['synced'] . '/' . $syncData['updated'] . PHP_EOL, 'parse-console');

            $this->stdout('Processed: ' . $syncData['processed'] . PHP_EOL);
            $this->stdout('Synced/Updated: ' . $syncData['synced'] . '/' . $syncData['updated'] . PHP_EOL);
        }
    }

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
        if ($this->sale === true) {
            
            $model->saleFlag = true;
            
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
                            $parser->syncProducts();

                            $this->stdout($detailsReturn . ' Detailed' . PHP_EOL . PHP_EOL);
                            Yii::info($detailsReturn . ' Detailed' . PHP_EOL . PHP_EOL, 'parse-console');
                        } else {
                            $this->stdout('.' . PHP_EOL);
                        }
                    }
                }
            }
        }
    }
}
