<?php

use yii\helpers\Html;
use yii\helpers\ArrayHelper;
use yii\grid\GridView;
use backend\models\Category;
use backend\models\CategorySource;
use backend\models\Source;
use backend\models\parser\Parser;
use backend\models\morph\Morph;
use yii\widgets\ActiveForm;

$this->title = 'Category Relations';
$this->params['breadcrumbs'][] = $this->title;

?>

<div class="category-source-index">

    <h1><?= Html::encode($this->title) ?></h1><br/>
    <?php // $morph = new Morph('ru'); print_r($morph->getPhraseLemmas('мфу')); ?>
    <?php // echo $this->render('_search', ['model' => $searchModel]); ?>


    <div class="form-group pull-right text-right">
        <?php ActiveForm::begin();?>
            <?= Html::submitButton('Выгрузить категории 0-го уровня в магазин', ['class' => 'btn btn-primary', 'name' => 'syncTree', 'value' => 1]) ?>
        <?php ActiveForm::end();?>
        <?php if ($syncData) : ?>
            <span style="position:relative;top:20px">Обработано: <b><?= $syncData['processed'] ?></b>, Синхронизировано: <b><?= count($syncData['synced']) ?></b></span><br/>
        <?php endif; ?>
    </div>

    <?= Html::a('Create Category Source', ['create'], ['class' => 'btn btn-success float-left']) ?><br/><br/>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'rowOptions' => function ($model) {
            return [
                'class' => $model->category->status ? 'text-primary' : 'text-danger'
            ];
        },
        'columns' => [
            'id',
            [
                'attribute' => 'status',
                'label'     => 'Parse Status',
                'filter'    => [
                    1 => 'On',
                    0 => 'Off',
                ],
                'content'   => function ($model) {
                    return Html::a(
                        $model->category->status ? 'ON' : 'OFF', 
                        [
                            'category/index', 
                            'CategorySearch[id]' => $model->category_id
                        ],
                        [
                            'title' => 'Перейти и изменить статус у соотв. глобальной категории',
                            'class' => $model->category->status ? 'text-primary' : 'text-danger',
                        ]
                    );
                },
                'contentOptions' => [
                    'style' => 'text-align: center',
                ],
            ],

            [
                'label'          => 'Tags',
                'format'         => 'raw',
                'value'          => function ($model) {
                    return '<span style="font-size: 11px; word-break: break-all; white-space: normal; width: 200px; overflow: hidden; display: block">' . implode('+', $model->tags) . '</span>';
                },
                'contentOptions' => [
                    'style' => 'width: 200px',
                ],
            ],

            [
                'attribute' => 'title',
                'label'     => 'Category Title',
                // 'filter'    => Category::listCategories(),
                'content'   => function ($model) {
                    return $model->category->title;
                },
                'contentOptions' => [
                    'style' => 'width: 350px; white-space: normal',
                ],
            ],


            [
                'attribute' => 'source_id',
                'label'     => 'Source',
                'filter'    => Source::listSources(),
                'content'   => function ($model) {
                    return Html::a($model->source->title, ['source/index', 'SourceSearch[id]' => $model->source_id]);
                },
            ],

            [
                'attribute' => 'self_parent_id',
                'label'     => 'Parent',
                'content'   => function ($model) {
                    $category = Category::find()->join('join', 'category_source cs', 'cs.category_id = category.id')->where(['cs.id' => $model->self_parent_id])->one();
                    return $category ? $category->title : '';
                },
                'filter'    =>  ArrayHelper::map( 
                    Category::find()
                        ->join('JOIN', 'category_source cs', 'cs.category_id = category.id')
                        ->all(), 
                    'id', 
                    'title' 
                ),
                'contentOptions' => [
                    'style' => 'width: 350px; white-space: normal',
                ],
            ],


            [
                'attribute' => 'nest_level',
                'filter'     => [0,1,2,3,4,5],
                'label'     => 'Nest',
            ],

            [
                'attribute' => 'source_url',
                'format'    => 'raw',
                'label'     => 'URL',
                'value'     => function($model) {
                    if ($model->source_url) {
                        $parser = new Parser();
                        $url = $parser->processUrl($model->source_url, $model->source_id);
                        return Html::a($url, $url, ['title' => 'URL Original: ' . $model->source_url]);
                    }
                },
                'contentOptions' => [
                    'style' => 'font-size: 11px; width: 350px; white-space: normal; word-break: break-all',
                ],
            ],

            [
                'class' => 'yii\grid\ActionColumn',
                // 'template' => '{view} {update}',
            ],
        ],
    ]); ?>
</div>
