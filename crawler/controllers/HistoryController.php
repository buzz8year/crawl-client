<?php
namespace crawler\controllers;

use Yii;
use yii\web\Controller;
use crawler\models\history\History;
use crawler\models\history\HistorySearch;

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
