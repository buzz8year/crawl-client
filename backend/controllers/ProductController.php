<?php

namespace backend\controllers;

use Yii;
use backend\models\Source;
use backend\models\Product;
use backend\models\ProductAttribute;
use backend\models\search\ProductSearch;
use backend\models\opencart\OcSettler;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;

/**
 * ProductController implements the CRUD actions for Product model.
 */
class ProductController extends Controller
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
     * Lists all Product models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new ProductSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        $syncData = [];

        if (Yii::$app->request->post('syncGoods')) {
            $syncData = OcSettler::saveProducts();
        }

        if (Yii::$app->request->post('deleteGoods')) {
           $this->deleteProducts();
        }

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'syncData' => $syncData,
        ]);
    }

    /**
     * Displays a single Product model.
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
     * Creates a new Product model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new Product();
        $modelAttribute = new ProductAttribute();

        if ($model->load(Yii::$app->request->post()) && $model->save() && $modelAttribute->load(Yii::$app->request->post()) && $modelAttribute->save()) {
            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('create', [
            'model' => $model,
            'modelAttribute' => $modelAttribute,
        ]);
    }

    /**
     * Updates an existing Product model.
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
     * Deletes an existing Product model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     */
    public function actionDelete($id)
    {
        $this->findModel($id)->delete();

        return $this->redirect(['index']);
    }

    public function deleteProducts(int $async = 0)
    {
        $sources = Source::find()->all();
        foreach ($sources as $source) {
            foreach ($source->products as $product) {
                if ($async) {
                    if ($product->sync_status == 0) {
                        $this->deleteDetails($product);
                        $product->delete();
                    }
                } else {
                    $this->deleteDetails($product);
                    $product->delete();
                }
            }
        }
    }

    public function deleteDetails($product)
    {
        if ($product->descriptions) {
            foreach ($product->descriptions as $description) {
                $description->delete();
            }
        }
        if ($product->productAttributes) {
            foreach ($product->productAttributes as $attribute) {
                $attribute->delete();
            }
        }
        if ($product->productImages) {
            foreach ($product->productImages as $image) {
                $image->delete();
            }
        }
    }

    /**
     * Finds the Product model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Product the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Product::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}
