<?php

use yii\helpers\Html;
use yii\helpers\ArrayHelper;
use yii\grid\GridView;
use backend\models\Category;
use backend\models\CategorySource;
use backend\models\Source;

$this->title = 'Category Relations';
$this->params['breadcrumbs'][] = $this->title;

?>

<div class="category-source-index">

    <h1><?= Html::encode($this->title) ?></h1>
    <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

    <p> <?= Html::a('Create Category Source', ['create'], ['class' => 'btn btn-success']) ?> </p><br/><br/>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'rowOptions' => function ($model) {
            return [
                'class' => $model->category->status ? 'text-primary' : ''
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
                    return $model->category->status ? 'ON' : 'OFF';
                },
                'contentOptions' => [
                    'style' => 'text-align: center',
                ],
            ],
            [
                'attribute' => 'title',
                'label'     => 'Category Title',
                // 'filter'    => Category::listCategories(),
                'content'   => function ($model) {
                    return $model->category->title;
                },
            ],

            [
                'attribute' => 'source_id',
                'label'     => 'Source',
                'filter'    => Source::listSources(),
                'content'   => function ($model) {
                    return $model->source->title;
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
            ],


            [
                'attribute' => 'source_url',
                'format'    => 'url',
                'label'     => 'URL',
                'contentOptions' => [
                    'style' => 'width: 350px; white-space: normal',
                ],
            ],

            [
                'class' => 'yii\grid\ActionColumn',
                // 'template' => '{view} {update}',
            ],
        ],
    ]); ?>
</div>
