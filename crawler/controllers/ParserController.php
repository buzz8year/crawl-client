<?php

namespace crawler\controllers;

use crawler\parser\ParserFactory;
use crawler\parser\ParserSettler;
use crawler\parser\ParserProvisioner;
use crawler\models\category\CategorySource;
use crawler\models\keyword\Keyword;
use crawler\models\source\Source;
use yii\web\Controller;
use Yii;

/**
 * ParserController
 * First, model is creates, then a particular parser class is instantiated & and all methods (also the ones of extended parser general class) are called via it.
 */
class ParserController extends Controller
{
    public function actionIndex()
    {
        return $this->render('index', [
            'sources' => ParserProvisioner::listSources(),
        ]);
    }

    /**
     * @param int $id - source ID
     * @param int $reg - region ID, can be null
     * @param int $cat - category ID, can be null
     * @param string $word - keyword value, can be empty
     * @return mixed
     */
    public function actionTrial(int $id, string $reg = '', string $cat = '', string $word = '', int $sale = 0)
    {
        $factory = new ParserFactory();
        $factory->setParser($id);
        $factory->handleRequest($reg, $cat, $word, $sale);

        $cachedTree = Yii::$app->cache->get('categoryTreeId=' . $factory->model->id);
        $rawCategories = CategorySource::findAllAsArrayById($id);

        // NOTE: Cached or build and then cache
        $sourceCategories = [];
        if ($cachedTree === false && $rawCategories) 
        {
            $sourceCategories = ParserProvisioner::buildTree($rawCategories);
            Yii::$app->cache->set('categoryTreeId=' . $factory->model->id, $sourceCategories);
        } 
        elseif ($cachedTree) 
            $sourceCategories = $cachedTree;

        // NOTE: Flush cache
        if (Yii::$app->request->post('flushTree')) 
        {
            Yii::$app->cache->delete('categoryTreeId=' . $factory->model->id);
            return Yii::$app->getResponse()->redirect(['parser/trial', 'id' => $id]);
        }

        // NOTE: Process keyword created on the fly
        if ($flyKeyword = Yii::$app->request->post('flyKeyword')) 
        {
            $flyKeyword = Keyword::getOrCreate($id, $flyKeyword, true);
            return Yii::$app->getResponse()
                ->redirect(['parser/trial', 'id' => $id, 'reg' => $reg, 'cat' => $cat, 'word' => $flyKeyword]);
        }

        // NOTE: Products
        if (Yii::$app->request->post('parseGoods') && ($cat || $word || $reg))
            $factory->parser->run();

        // NOTE: Product details
        // NOTE: Details of all already present products
        if ($urlProducts = json_decode(Yii::$app->request->post('parseDetails') ?? '', true)) 
        {
            $factory->model->details = $urlProducts;
            $factory->parser->parseDetails();
        }

        if (Yii::$app->request->post('updateDetails')) 
        {
            $presentProducts = Source::findOne($factory->model->id)->productUrls;
            $factory->model->details = $presentProducts;
            $factory->parser->parseDetails();
        }

        // NOTE: Synced products data
        $syncData = [];
        if (Yii::$app->request->post('syncGoods'))
            $syncData = $factory->parser->settler->syncProducts();

        return $this->render('trial', [
            'model'            => $factory->model,
            'sourceCategories' => $sourceCategories,
            'sourceKeywords'   => ParserProvisioner::listSourceKeywords($id),
            'sourceRegions'    => ParserProvisioner::listSourceRegions($id),
            'detailsParsed'    => empty($factory->model->details) ? 0 : count($factory->model->details),
            'syncData'         => $syncData,
        ]);
    }

    /**
     * actionTree() method displays current category tree state & parses for it over the source by request.
     * @param int $id - source ID
     * @return mixed
     */
    public function actionTree(int $id)
    {
        if (($session = Yii::$app->session) && $session->isActive)
            $session->close();

        $factory = new ParserFactory();        
        $factory->setParser($id);

        $model = $factory->model;
        $parser = $factory->parser;

        $provisioner = new ParserProvisioner();
        $categories = $provisioner->buildTree(CategorySource::findAllAsArrayById($id));

        usort($categories, function ($a, $b) {
            return count($b['children']) - count($a['children']);
        });

        $parsedCategories = [];
        $missedCategories = [];
        $error = false;

        if (Yii::$app->request->post()) 
        {
            $settler = new ParserSettler($factory);

            if (Yii::$app->request->post('parseTree')) 
            {
                if ($parsedCategories = $parser->parseCategories())
                    $missedCategories = $settler->nestMisses($parsedCategories);
                else $error = true;
            }

            if ($postParsed = Yii::$app->request->post('saveChanges')) 
            {
                $settler->saveCategories(json_decode($postParsed));
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
     * Method works fine, yet target method displayNestedSelect() of ParserProvisioner class
     * fails on returning proper result by using "$html .=" instead of "echo".
     * No crucial need for now.
     */
    // public function actionLoadSelect() {
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
     */
    // public function actionBuildUrl() {
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
