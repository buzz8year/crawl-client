<?php

namespace crawler\controllers;

use Yii;
use crawler\models\source\Source;
use crawler\models\product\Product;
use crawler\models\product\ProductAttribute;
use crawler\models\product\ProductSearch;
use crawler\models\parser\Parser;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;

use crawler\models\opencart\OcSettler;
use crawler\models\sync\YiiShopSettler;


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

        // $ocProducts = OcSettler::countProducts();

        $syncData = [];

        if (Yii::$app->request->post('syncGoods')) {
            // $syncData = OcSettler::saveProducts();
            foreach (Product::find()->all() as $product) {
                $product->sync_status = 1;
                $product->save();
            }
        }

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            // 'ocProducts' => $ocProducts,
            'syncData' => $syncData,
        ]);
    }

    public function actionSyncProduct(int $id)
    {
        // YiiShopSettler::saveProduct($this->findModel($id));
        if (YiiShopSettler::saveProduct($this->findModel($id))) {
            echo 'ok';
        } else {
            echo 'not ok';
        }
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
     * Updates an existing Product model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     */
    public function actionUpdateDetails($id)
    {
        $model = $this->findModel($id);

        $super = new Parser();
        $modelParser = $super->createModel($model->source_id);
        $parser = new $modelParser->class();

        $parser->parseDetails([$model->id => $model->source_url]);

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

    /**
     * Deletes all Products and theit details.
     * @return mixed
     */
    public function actionDeleteMisfits()
    {
        $data = OcSettler::deleteMisfits();
        echo 'Total processed: ' . $data['total'] . '<br/>';
        echo 'Misfits deleted: ' . $data['misfits'] . '<br/>';
    }

    /**
     * Deletes all Products and theit details.
     * @return mixed
     */
    public function actionDeleteAll()
    {
        $this->deleteProducts();
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
