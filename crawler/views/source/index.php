<?php

use yii\helpers\Html;
use yii\grid\GridView;
use \crawler\models\source\Source;

/* @var $this yii\web\View */
/* @var $searchModel crawler\models\search\SourceSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Sources';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="source-index">

    <h1><?= Html::encode($this->title) ?></h1>
    <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

    <p> <?= Html::a('Create Source', ['create'], ['class' => 'btn btn-success']) ?> </p><br/><br/>


    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'tableOptions' => [
            'class' => 'table'
        ],
        'rowOptions' => function ($model) {
            
            return [
                'class' => $model->status ? 'bg-success' : ''
            ];

        },
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],

            // 'id',
            'title',
            'source_url:url',
            // 'description:ntext',
            // 'class_namespace',
            [
                'attribute' => 'status',
                'format'    => 'raw',
                'filter'    => [
                    1 => 'ON', 
                    0 => 'OFF'
                ],
                'value'   => function ($model) {
                    return $model->status ? 'ON' : 'OFF';
                },
            ],
            // [
            //     'attribute' => 'need_synonymizer',
            //     'format'    => 'text',
            //     'filter'    => Source::synonymizeStatusText(),
            //     'content'   => function ($model) {
            //         return $model->synonymizeStatus();
            //     },
            //     'contentOptions' => function($model) {
            //         return [
            //             'class' => $model->synonymizeHighlight()
            //         ];
            //     }
            // ],
            // [
            //     'attribute' => 'need_proxy',
            //     'format'    => 'text',
            //     'filter'    => Source::proxyStatusText(),
            //     'content'   => function ($model) {
            //         return $model->proxyStatus();
            //     },
            //     'contentOptions' => function($model) {
            //         return [
            //             'class' => $model->proxyHighlight()
            //         ];
            //     }
            // ],
            // [
            //     'attribute' => 'need_captcha',
            //     'format'    => 'text',
            //     'filter'    => Source::captchaStatusText(),
            //     'content'   => function ($model) {
            //         return $model->captchaStatus();
            //     },
            //     'contentOptions' => function($model) {
            //         return [
            //             'class' => $model->captchaHighlight()
            //         ];
            //     }
            // ],

            [
                'class'    => 'yii\grid\ActionColumn',
                'template' => '{view} {update}',
            ],
        ],
    ]); ?>
</div>
