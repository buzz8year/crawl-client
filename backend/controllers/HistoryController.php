<?php
namespace backend\controllers;

use Yii;
use yii\web\Controller;
use backend\models\History;
use backend\models\search\HistorySearch;


class HistoryController extends Controller
{



    public function actionIndex()
    {
        $searchModel = new HistorySearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        // $dataProvider = History::find()->all();

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }



}
