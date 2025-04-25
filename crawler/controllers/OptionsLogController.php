<?php

namespace crawler\controllers;

use Yii;
use crawler\models\options\OptionsLog;
use crawler\models\options\OptionsLogSearch;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;

/**
 * OptionsLogController implements the CRUD actions for OptionsLog model.
 */
class OptionsLogController extends Controller
{
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'delete' => ['POST'],
                ],
            ],
        ];
    }

    /**
     * Lists all OptionsLog models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new OptionsLogSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }
}
