<?php

namespace crawler\controllers;

use yii\web\NotFoundHttpException;
use crawler\models\header\Header;
use crawler\models\header\HeaderSource;
use crawler\models\header\HeaderValue;
use crawler\models\keyword\Keyword;
use crawler\models\keyword\KeywordSource;
use crawler\models\proxy\Proxy;
use crawler\models\proxy\ProxySource;
use crawler\models\region\Region;
use crawler\models\region\RegionSource;
use crawler\models\source\SourceSearch;
use crawler\models\source\Source;
use yii\filters\VerbFilter;
use yii\web\Controller;
use Yii;

class SourceController extends Controller
{
    public function behaviors()
    {
        return [
            'verbs' => [
                'class'   => VerbFilter::class,
                'actions' => [
                    'delete' => ['POST'],
                ],
            ],
        ];
    }

    protected function findModel($id)
    {
        if (($model = Source::findOne($id)) !== null)
            return $model;

        throw new NotFoundHttpException('The requested page does not exist.');
    }

    public function actionIndex()
    {
        $searchModel  = new SourceSearch();
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
        $model = new Source();

        if ($model->load(Yii::$app->request->post()) && $model->save()) 
        {
            foreach (Yii::$app->request->post('keywords') ?? [] as $word) 
            {
                if ($word != '') 
                {
                    $keywordSource  = new KeywordSource();
                    $keywordSource->source_id = $model->id;
                    $foundWord = Source::findKeyword($word);

                    if (boolval($foundWord)) 
                    {
                        $keyword = new Keyword();
                        $keyword->word = $word;
                        $keyword->save();

                        $keywordSource->keyword_id = $keyword->id;
                    } 
                    else $keywordSource->keyword_id = (int)$foundWord->id;

                    $keywordSource->save();
                }
            }

            return $this->redirect(['view', 'id' => $model->id]);
        } 
        else {
            return $this->render('create', [
                'model' => $model,
            ]);
        }
    }

    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post()) && $model->save()) 
        {
            foreach ($model->keywordSources as $keywordSource)
                $keywordSource->delete();

            if (Yii::$app->request->post('keywords')) 
            {
                foreach (Yii::$app->request->post('keywords') as $word) 
                {
                    if ($word != '' && !in_array($word, $model->keywords ? $model->keywords : [])) 
                    {
                        $keywordSource = new KeywordSource();
                        $keywordSource->source_id = $model->id;
                        $foundWord = Source::findKeyword($word);

                        if (boolval($foundWord)) 
                        {
                            $keyword = new Keyword();
                            $keyword->word = $word;
                            $keyword->save();

                            $keywordSource->keyword_id = $keyword->id;
                        } 
                        else $keywordSource->keyword_id = $foundWord->id;

                        $keywordSource->save();
                    }
                }
            }

            foreach ($model->regionSources as $regionSource)
                $regionSource->delete();

            if (Yii::$app->request->post('regions')) 
            {
                foreach (Yii::$app->request->post('regions') as $region) 
                {
                    $exp = explode('+', $region);
                    $region = $exp[0];
                    $status = $exp[1] ?? 1;

                    if ($region != '' && !in_array($region, $model->regions ? $model->regions : [])) 
                    {
                        $regionSource            = new RegionSource();
                        $regionSource->source_id = $model->id;
                        $regionSource->status = $status;

                        if (count($foundRegion = Source::findRegion($region)) < 1) 
                        {
                            $newRegion        = new Region();
                            $newRegion->alias = $region;
                            $newRegion->save();
                            $regionSource->region_id = $newRegion->id;
                        } 
                        else $regionSource->region_id = $foundRegion->id;

                        $regionSource->save();
                    }
                }
            }

            $session = Yii::$app->session;
            $session->setFlash('source-update', 'Data successfully updated.');
            return $this->redirect(['source/update', 'id' => $id]);
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

    public function actionDeleteCategories($id)
    {
        $model = $this->findModel($id);
        $count = count($model->categorySources);

        foreach ($model->categorySources as $category) {
            $category->delete();
        }

        Yii::$app->cache->delete('categoryTreeId=' . $id);

        $session = Yii::$app->session;
        $session->setFlash('categories-deleted', sprintf('All (%d) categories linked to the resource have been deleted.', $count));

        return $this->redirect(['source/update', 'id' => $id]);
    }

    public function actionDeleteProducts(int $id, int $async = 0)
    {
        $model = $this->findModel($id);
        $count = count($model->products);
        $session = Yii::$app->session;

        foreach ($model->products as $product) 
        {
            if ($async) 
            {
                if ($product->sync_status == 0) 
                {
                    $this->deleteDetails($product);
                    $product->delete();
                    $session->setFlash('products-deleted', 'All unsynchronized products of the resource have been deleted.');
                }
            } 
            else {
                $this->deleteDetails($product);
                $product->delete();
                $session->setFlash('products-deleted', sprintf('All (%d) products of the resource have been deleted.', $count));
            }
        }

        return $this->redirect(['source/update', 'id' => $id]);
    }

    public function deleteDetails($product)
    {
        if ($product->descriptions) {
            foreach ($product->descriptions as $description)
                $description->delete();
        }
        if ($product->productAttributes) {
            foreach ($product->productAttributes as $attribute)
                $attribute->delete();
        }
        if ($product->productImages) {
            foreach ($product->productImages as $image)
                $image->delete();
        }
    }

    public function actionHeaderCreate($hid, $sid)
    {
        $headerSource = new HeaderSource();
        $headerSource->header_id = HeaderValue::findOne($hid)->header_id;
        $headerSource->header_value_id = $hid;
        $headerSource->source_id = $sid;
        $headerSource->save();

        $text = Header::findOne($headerSource->header_id)->title . ': ' 
            . HeaderValue::findOne($hid)->value;

        return $headerSource->save() ? $text : 'error';
    }

    public function actionHeaderStatus($id)
    {
        $headerSource = HeaderSource::findOne($id);
        $headerSource->status = $headerSource->status ? 0 : 1;
        $headerSource->save();

        return $headerSource->status;
    }

    public function actionHeaderQueue($id, $queue)
    {
        $headerSource = HeaderSource::findOne($id);

        if ($headerSource->queue != $queue) {
            $headerSource->queue = $queue;
            $headerSource->save();
            return $headerSource->queue;
        }
    }

    public function actionHeaderDelete($id)
    {
        $headerSource = HeaderSource::findOne($id);
        $headerSource->delete();
        return $headerSource ? 1 : 0;
    }

    public function actionProxyCreate($pid, $sid)
    {
        $proxy = Proxy::findOne($pid);

        $proxySource = new ProxySource();
        $proxySource->proxy_id = $proxy->id;
        $proxySource->source_id = $sid;
        $proxySource->save();

        $address = $proxy->ip . ($proxy->port ? ':' . $proxy->port : '');

        return $proxySource->save() ? $address : 'error';
    }

    public function actionProxyStatus($id)
    {
        $proxySource = ProxySource::findOne($id);
        $proxySource->status = $proxySource->status ? 0 : 1;
        $proxySource->save();

        return $proxySource->status;
    }

    public function actionProxyQueue($id, $queue)
    {
        $proxySource = ProxySource::findOne($id);

        if ($proxySource->queue != $queue) {
            $proxySource->queue = $queue;
            $proxySource->save();
            return $proxySource->queue;
        }
    }

    public function actionProxyDelete($id)
    {
        $proxySource = ProxySource::findOne($id);
        $proxySource->delete();
        return $proxySource ? 1 : 0;
    }

    public function actionRegionStatus(int $id, int $source)
    {
        $regionSource = RegionSource::findOne($id);
        $globalRegion = RegionSource::find()->where(['source_id' => $source, 'status' => 2])->one();

        if ($globalRegion && $globalRegion != $regionSource)
            return 'Within a resource, there can only be one global region!';

        $regionSource->status = $regionSource->status == 2 ? 1 : 2;
        $regionSource->save();

        return $regionSource->status;
    }
}
