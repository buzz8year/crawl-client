<?php

namespace backend\controllers;

use Yii;
use backend\models\OptionsLog;
use backend\models\search\OptionsLogSearch;
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
                'class' => VerbFilter::className(),
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
