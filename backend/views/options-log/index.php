<?php

use yii\helpers\Html;
use yii\grid\GridView;
use backend\models\Source;
use backend\models\Header;
use backend\models\Proxy;

/* @var $this yii\web\View */
/* @var $searchModel backend\models\search\OptionsLogSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Options Logs';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="row">
    <div class="col-lg-8">
        <h1><?= Html::encode($this->title) ?></h1>
    </div>
    <div class="col-lg-4 text-right">
        <h3>&nbsp;</h3>
        <span><?php echo Html::a('Общая история', ['history/index']); ?></span>
    </div>
</div><br/><br/>

<div class="options-log-index">

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'columns' => [
            // 'id',
            // 'url:url',
            [
                'format' => 'raw',
                'attribute' => 'url',
                'value' => function($model){
                    return Html::a('<i class="glyphicon glyphicon-share"></i>', $model->url, ['target' => '_blank']);
                },
            ],
            'date',
            // 'source_id',
            [
                'format' => 'raw',
                'attribute' => 'source_id',
                'value' => function($model){
                    return Html::a(Source::findOne($model->source_id)->title, ['source/index', 'SourceSearch[id]' => $model->source_id]);
                },
            ],
            // 'proxy_id',
            [
                'format' => 'raw',
                'attribute' => 'proxy_id',
                'value' => function($model){
                    if ($model->proxy_id) {
                        return Html::a(Proxy::findOne($model->proxy_id)->ip, ['proxy/index', 'ProxySearch[id]' => $model->proxy_id]);
                    }
                },
            ],
            // 'header_value_id',
            [
                'format' => 'raw',
                'attribute' => 'header_value_id',
                'value' => function($model){
                    if ($model->header_value_id) {
                        $header = Header::find()
                            ->select(['header.id as id', 'header.title', 'hv.value'])
                            ->join('join', 'header_value hv', 'hv.header_id = header.id')
                            ->where(['hv.id' => $model->header_value_id])
                            ->asArray()
                            ->one();
                        return Html::a($header['title'] . ': ' . $header['value'], ['header/update', 'id' => $header['id']], ['style' => 'font-size: 12px']);
                    }
                },
            ],
            'client',
            // 'status',
            [
                'format' => 'raw',
                'filter' => [
                    0 => 'OK',
                    1 => 'Proxy Fail',
                    2 => 'Header Fail',
                ],
                'attribute' => 'status',
                'value' => function($model){
                    switch ($model->status) {
                        case 0:
                            return Html::tag('span', 'OK', ['class' => 'label label-success']);
                        case 1:
                            return Html::tag('span', 'proxy', ['class' => 'label label-danger']);
                        case 2:
                            return Html::tag('span', 'header', ['class' => 'label label-danger']);
                    }
                },
            ],

            // ['class' => 'yii\grid\ActionColumn'],
        ],
    ]); ?>
</div>
