<?php

namespace crawler\controllers;

use crawler\models\proxy\Proxy;
use crawler\models\proxy\ProxySearch;
use Yii;
use crawler\models\spource\Source;
use crawler\models\proxy\ProxySource;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;

/**
 * ProxyController implements the CRUD actions for Proxy model.
 */
class ProxyController extends Controller
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
     * Lists all Proxy models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new ProxySearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single Proxy model.
     * @param integer $id
     * @return mixed
     */
    public function actionView($id)
    {
        return $this->render('view', [
            'model' => $this->findModel($id),
        ]);
    }

    /**
     * Creates a new Proxy model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new Proxy();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }

    /**
     * Updates an existing Proxy model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('update', [
            'model' => $model,
        ]);
    }

    /**
     * Deletes an existing Proxy model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     */
    public function actionDelete($id)
    {
        $this->findModel($id)->delete();

        return $this->redirect(['index']);
    }

    /**
     * Assigns an existing Proxy model to all sources globally.
     * @param integer $id
     * @return mixed
     */
    public function actionAssignGlobal($id)
    {
        foreach (Source::find()->all() as $source) {
            $proxySourceExist = ProxySource::find()->where(['source_id' => $source->id])->one();

            if (!$proxySourceExist) {
                $newProxySource = new ProxySource();
                $newProxySource->proxy_id = $id;
                $newProxySource->source_id = $source->id;
                $newProxySource->save();
            }
        }

        $session = Yii::$app->session;
        $session->setFlash('proxy-global', 'Прокси подписан ко всем ресурсам.');
        return $this->redirect(['proxy/update', 'id' => $id]);
    }

    /**
     * Finds the Proxy model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Proxy the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Proxy::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}
