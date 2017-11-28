<?php

namespace backend\controllers;

use backend\models\Category;
use backend\models\CategorySource;
use backend\models\History;
use backend\models\Keyword;
use backend\models\KeywordSource;
use backend\models\parser\Parser;
use backend\models\parser\ParserProvisioner;
use backend\models\parser\ParserSettler;
use backend\models\Product;
use Yii;
use yii\web\Controller;

/**
 * ParserController
 * First, model is creates, then a particular parser class is instantiated & and all methods (also the ones of extended parser general class) are called via it.
 */
class ParserController extends Controller
{
    /**
     * @param int $sourceId
     * @return DynamicModel object
     */
    protected function getModel(int $sourceId)
    {
        $parser = new Parser();
        return $parser->createModel($sourceId);
    }

    /**
     * @return mixed
     */
    public function actionIndex()
    {
        $provisioner = new ParserProvisioner();
        $sources     = $provisioner->listSources();

        return $this->render('index', [
            'sources' => $sources,
        ]);
    }

    /**
     * @param int $id - source ID
     * @param int $reg - region ID, can be null
     * @param int $cat - category ID, can be null
     * @param string $word - keyword value, can be empty
     * @return mixed
     */
    public function actionTrial(int $id, string $reg = '', string $cat = '', string $word = '')
    {
        // SESSION: Close
        if (($session = Yii::$app->session) && $session->isActive) {
            $session->close();
        }

        // MODEL: Creates model object
        $model  = $this->getModel($id);
        $parser = new $model->class();

        // INPUTS: Process and save to model
        $model->regionId = $reg;

        if ($cat) {
            $categorySource          = CategorySource::find()->where(['source_id' => $id, 'category_id' => $cat])->one();
            $model->categorySourceId = $categorySource ? $categorySource->id : null;
            $model->categoryId       = $cat;
        }

        if ($word) {
            $keyword          = Keyword::find()->where(['word' => $word])->one();
            $model->keywordId = $keyword ? $keyword->id : null;
        }

        $notVoid = $reg || $cat || $word;

        // URL: Build
        if ($notVoid) {
            $model->url = $parser->urlBuild($model->regionId, $model->categorySourceId ?? '', $word);
        }

        // VIEW: Rendering needs
        $provisioner = new ParserProvisioner();
        $settler     = new ParserSettler();

        $sourceRegions  = $provisioner->listSourceRegions($id);
        $sourceKeywords = $provisioner->listSourceKeywords($id);

        $rawCategories = CategorySource::find()->where(['source_id' => $id])->orderBy('nest_level')->asArray()->all();
        $cachedTree = Yii::$app->cache->get('categoryTreeId=' . $model->id);

        // TREE: Cached or build and then cache
        if ($cachedTree === false && $rawCategories) {
            $sourceCategories = ParserProvisioner::buildTree($rawCategories);
            Yii::$app->cache->set('categoryTreeId=' . $model->id, $sourceCategories);
        } else {
            $sourceCategories = $cachedTree;
        }

        // TREE: Flush cache
        if (Yii::$app->request->post('flushTree')) {
            Yii::$app->cache->delete('categoryTreeId=' . $model->id);
            return Yii::$app->getResponse()->redirect(['parser/trial', 'id' => $id]);
        }

        // KEYWORD: Process keyword created on the fly
        if ($flyKeyword = Yii::$app->request->post('flyKeyword')) {
            $flyKeyword = $this->processKeyword($id, $flyKeyword);
            return Yii::$app->getResponse()->redirect(['parser/trial', 'id' => $id, 'reg' => $reg, 'cat' => $cat, 'word' => $flyKeyword]);
        }

        // PARSE: Products
        if (Yii::$app->request->post('parseGoods') && $notVoid) {
            $parser->parseProducts();
        }



        $detailsParsed = 0;

        // PARSE: Products' details
        if ($urlProducts = json_decode(Yii::$app->request->post('parseDetails'), true)) {
            $parser->parseDetails($urlProducts);
            $detailsParsed = count($urlProducts);
        }

        return $this->render('trial', [
            'model'            => $model,
            'sourceRegions'    => $sourceRegions,
            'sourceCategories' => $sourceCategories,
            'sourceKeywords'   => $sourceKeywords,
            'detailsParsed'    => $detailsParsed,
        ]);
    }

    /**
     * actionTree() method displays current category tree state & parses for it over the source by request.
     * @param int $id - source ID
     * @return mixed
     */
    public function actionTree(int $id)
    {
        if (($session = Yii::$app->session) && $session->isActive) {
            $session->close();
        }

        $model  = $this->getModel($id);
        $parser = new $model->class();

        $sourceCategories = CategorySource::find()->where(['source_id' => $id])->orderBy('nest_level')->asArray()->all();

        $provisioner = new ParserProvisioner();
        $categories  = $provisioner->buildTree($sourceCategories);

        usort($categories, function ($a, $b) {
            return (count($b['children']) - count($a['children']));
        });

        $parsedCategories = [];
        $missedCategories = [];

        $error = false;

        if (Yii::$app->request->post()) {

            $settler = new ParserSettler($id);

            if (Yii::$app->request->post('parseTree')) {
                $parsedCategories = $parser->parseCategories();
                if ($parsedCategories) {
                    $missedCategories = $settler->nestMisses($parsedCategories);
                } else {
                    $error = true;
                }
            }

            if ($postParsed = Yii::$app->request->post('saveChanges')) {
                $saveCategories = $settler->saveCategories(json_decode($postParsed));
                Yii::$app->cache->delete('categoryTreeId=' . $model->id);
                return Yii::$app->getResponse()->redirect(['parser/tree', 'id' => $id]);
            }

        }

        return $this->render('tree', [
            'model'            => $model,
            'categories'       => $categories,
            'parsedCategories' => $parsedCategories,
            'misses'           => $missedCategories,
            'error'            => $error,
        ]);

    }

    /**
     * This action method was made for the case of ajax need
     * Uncomment when needed
     * @param int $id - source id
     * @param string $word - keyword
     * @return function
     */
    public function processKeyword(int $id, string $word)
    {
        $findWord = Keyword::find()->where(['word' => $word])->one();
        if ($findWord) {
            $keywordSource = KeywordSource::find()->where(['keyword_id' => $findWord->id])->one();
            if (!$keywordSource) {
                $keywordSource = new KeywordSource();
                $keywordSource->keyword_id = $findWord->id;
                $keywordSource->source_id = $id;
                $keywordSource->save();
            }
        } else {
            $newKeyword = new Keyword();
            $newKeyword->word = $word;
            $newKeyword->save();

            $keywordSource = new KeywordSource();
            $keywordSource->keyword_id = $newKeyword->id;
            $keywordSource->source_id = $id;
            $keywordSource->save();
        }

        $keyword = Keyword::findOne($keywordSource->keyword_id)->word;

        return $keyword;
    }

    /**
     * This action method was made for the case of ajax need
     * Method works fine, yet target method displayNestedSelect() of ParserProvisioner class
     * fails on returning proper result by using "$html .=" instead of "echo".
     * No crucial need for now.
     * @return function
     */
    // public function actionLoadSelect()
    // {
    //     $sourceCategories  = ParserProvisioner::buildTree((array)json_decode(Yii::$app->request->post()['cats']));

    //     return ParserProvisioner::displayNestedSelect(
    //         $sourceCategories,
    //         Yii::$app->request->post()['sourceId'],
    //         Yii::$app->request->post()['regionId'],
    //         Yii::$app->request->post()['currentCat']
    //     );
    // }

    /**
     * This action method was made for the case of ajax need
     * Uncomment when needed
     * @return function
     */
    // public function actionBuildUrl()
    // {
    //     $super = new Parser();
    //     $class = $super->getClass( Yii::$app->request->post()['sourceId'] );
    //     $parser = new $class ();

    //     return $parser->buildUrl(
    //         Yii::$app->request->post()['categorySourceId'],
    //         Yii::$app->request->post()['keyword'],
    //         Yii::$app->request->post()['inputValue']
    //     );
    // }

}
