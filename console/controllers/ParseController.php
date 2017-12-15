<?php

namespace console\controllers;

use backend\models\parser\Parser;
use backend\models\parser\ParserProvisioner;
use backend\models\Product;
use Yii;

class ParseController extends \yii\console\Controller
{
    const ACTIVE_TITLE     = 'АКТИВНЫЕ: Ресурсы и Категории';
    const QUANTITY_TITLE   = 'Кол-во товаров';
    const DELIMITER_NUMBER = 100;

    public $src;
    public $sale = false;

    public function options($action)
    {
        // $options = parent::options($action);
        // if ($action == 'parse') {
            $options[] = 'src';
            $options[] = 'sale';
        // }
        return $options;
    }

    // public function actionIndex(int $id = null, string $saleFlag = '', string $reg = '', string $cat = '', string $word = '')
    // public function actionIndex(int $id = null, string $saleFlag = '')
    public function actionIndex()
    {
        // if ($id) {
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
        $sources = ParserProvisioner::activeSources();

        $delimiterWrap = self::DELIMITER_NUMBER - mb_strlen(self::ACTIVE_TITLE) - 1;

        $this->stdout(
            PHP_EOL . PHP_EOL .
            self::ACTIVE_TITLE .
            str_repeat(' ', $delimiterWrap) . '↓' .
            PHP_EOL . PHP_EOL
        );

        foreach ($sources as $sourceId => $source) {
            $categories      = ParserProvisioner::activeCategories($sourceId);
            $delimiterSource = self::DELIMITER_NUMBER - mb_strlen($source['title']) - mb_strlen(self::QUANTITY_TITLE);

            $this->stdout(
                PHP_EOL . PHP_EOL .
                strtoupper($source['title']) .
                str_repeat(' ', $delimiterSource) .
                self::QUANTITY_TITLE .
                PHP_EOL . PHP_EOL
            );

            foreach ($categories as $categoryId => $category) {
                $countProducts     = count(Product::find()->where(['category_id' => $categoryId])->all());
                $countProducts     = $countProducts ? (' ' . $countProducts) : '';
                $delimiterCategory = self::DELIMITER_NUMBER - mb_strlen($category['title'] . ' ') - strlen((string) $countProducts);

                $this->stdout(
                    $category['title'] . ' ' .
                    str_repeat('-', $delimiterCategory) .
                    $countProducts .
                    PHP_EOL
                );
            }
        }
        $this->stdout(
            PHP_EOL . PHP_EOL .
            self::ACTIVE_TITLE .
            str_repeat(' ', $delimiterWrap) . '↑' .
            PHP_EOL . PHP_EOL
        );

    }

    public function parseSource(int $sourceId)
    {
        $super       = new Parser();
        $provisioner = new ParserProvisioner();

        $model  = $super->createModel($sourceId);
        $parser = new $model->class();

        if ($this->sale === true) {
            $model->saleFlag = true;
        }

        $this->stdout(
            PHP_EOL . PHP_EOL .
            str_repeat('|', mb_strlen($model->title)) .
            PHP_EOL . strtoupper($model->title) .
            PHP_EOL . PHP_EOL
        );

        $categories = $provisioner->activeCategories($sourceId);
        $keywords   = $provisioner->listSourceKeywords($sourceId);

        $this->stdout('Categories Found: ' . count($categories) . PHP_EOL);
        $this->stdout('Keywords Found: ' . count($keywords) . PHP_EOL . PHP_EOL);

        // LOG: console/runtime/logs/parse.log
        Yii::info('Categories to parse: ' . count($categories) . PHP_EOL, 'parse-console');
        Yii::info('Keywords to parse: ' . count($keywords) . PHP_EOL, 'parse-console');

        // if ($keywords) {
        //     $this->stdout('ITERATE: Keywords' . PHP_EOL . PHP_EOL);

        //     foreach ($keywords as $keywordId => $keyword) {
        //         $model->keywordId = $keywordId;
        //         $model->url       = $parser->urlBuild('', '', $keyword);

        //         if ($productsReturn = $parser->parseProducts()) {
        //             $this->stdout(
        //                 $model->url . PHP_EOL .
        //                 $productsReturn . ' Products' . PHP_EOL
        //             );
        //             Yii::info($model->url . PHP_EOL . $productsReturn . ' Products' . PHP_EOL, 'parse-console');
        //         }
        //         if ($detailsReturn = $parser->parseDetails()) {
        //             $parser->syncProducts();

        //             $this->stdout($detailsReturn . ' Detailed' . PHP_EOL . PHP_EOL);
        //             Yii::info($detailsReturn . ' Detailed' . PHP_EOL . PHP_EOL, 'parse-console');
        //         } else {
        //             $this->stdout('.' . PHP_EOL);
        //         }
        //     }
        // }

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
