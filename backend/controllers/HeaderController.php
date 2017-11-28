<?php

namespace backend\controllers;

use yii\web\Controller;
use backend\models\Header;
use backend\models\Source;
use backend\models\HeaderSource;
use backend\models\HeaderValue;
use backend\models\search\HeaderSearch;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use Yii;

class HeaderController extends Controller
{

    public function behaviors()
    {
        return [
            'verbs' => [
                'class'   => VerbFilter::className(),
                'actions' => [
                    'delete' => ['POST'],
                ],
            ],
        ];
    }

    public function actionIndex()
    {
        $searchModel  = new HeaderSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel'  => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    public function actionView($id)
    {
        return $this->render('view', [
            'model' => $this->findModel($id),
        ]);
    }

    public function actionCreate()
    {
        $model = new Header();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {

            if (($newValues = Yii::$app->request->post('header-new-values'))) {
                foreach ($newValues as $value) {
                    if ($value != '' && !(HeaderValue::find()->where(['header_id' => $id, 'value' => $value])->one())) {
                        $headerValue            = new HeaderValue();
                        $headerValue->header_id = $id;
                        $headerValue->value     = $value;

                        $headerValue->save();
                    }
                }
            }

            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }

    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {

            if (($oldValues = Yii::$app->request->post('header-old-values'))) {
                foreach ($oldValues as $valueID => $value) {
                    $valueExisting = HeaderValue::findOne($valueID);

                    if ($valueExisting && $valueExisting->value != $value[0]) {
                        $valueExisting->value = $value[0];
                        $valueExisting->save();
                    }

                }
            }

            if (($newValues = Yii::$app->request->post('header-new-values'))) {
                foreach ($newValues as $value) {
                    if ($value != '' && !(HeaderValue::find()->where(['header_id' => $id, 'value' => $value])->one())) {
                        $headerValue            = new HeaderValue();
                        $headerValue->header_id = $id;
                        $headerValue->value     = $value;

                        $headerValue->save();
                    }
                }
            }

            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('update', [
            'model' => $model,
        ]);
    }

    public function actionDelete($id)
    {
        $this->findModel($id)->delete();

        return $this->redirect(['index']);
    }

    public function actionValueDelete($id)
    {
        $value       = HeaderValue::findOne($id);
        $sourceValue = HeaderSource::find()->where(['header_value_id' => $id])->one();
        if ($sourceValue) {
            return 'Значение подписано на ресурс ' . Source::findOne($sourceValue->source_id)->title . ', указанный левее!';
        }
        $value->delete();

        return 'deleted';
    }

    protected function findModel($id)
    {
        if (($model = Header::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}
