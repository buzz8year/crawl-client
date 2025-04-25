<?php

use yii\grid\GridView;
use yii\helpers\Html;
use crawler\models\Category;

/* @var $this yii\web\View */
/* @var $searchModel crawler\models\search\CategorySearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title                   = 'Categories';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="category-index">

    <h1><?=Html::encode($this->title)?></h1>
    <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

    <p>
        <?=Html::a('Create Category', ['create'], ['class' => 'btn btn-success'])?>
    </p><br/><br/>

    <?php 
    // foreach (Category::countTagUsage() as $tagId => $tagCount) {
    //     echo $tagId . ': ' . $tagCount . '<br/>';
    // } 
    ?>

    <?=GridView::widget([
    'dataProvider' => $dataProvider,
    'filterModel'  => $searchModel,
    'columns'      => [
        // ['class' => 'yii\grid\SerialColumn'],

        // 'id',
        [
            'attribute'      => 'id',
            'label'          => 'ID',
            'contentOptions' => [
                'style' => 'width: 75px; text-align: center',
            ],
        ],
        [
            'attribute'      => 'category_outer_id',
            'label'          => 'OuterID',
            'contentOptions' => [
                'style' => 'width: 75px',
            ],
        ],
        [
            'attribute'      => 'source',
            'label'          => 'Подписанные ресурсы',
            'format'         => 'raw',
            'value'          => function ($model) {
                $links = [];
                foreach ($model->sources as $sourceCategoryId => $source) {
                    $links[] = Html::a($source, ['category-source/index', 'CategorySourceSearch[id]' => $sourceCategoryId], []);
                }
                return '<span style="white-space: normal; width: 300px; overflow: hidden; display: block">' . implode(', ', $links) . '</span>';
            },
            'contentOptions' => [
                'style' => 'width: 300px',
            ],
        ],
        [
            'attribute'      => 'title',
            'contentOptions' => [
                'style' => 'width: 100%',
            ],
        ],
        // 'status',
        [
            'attribute'      => 'status',
            'format'         => 'raw',
            'filter' => [
                1 => 'ON',
                0 => 'OFF',
            ],
            'header'         => '<a style="cursor: pointer; white-space: nowrap" onclick="$(\'.cat-status\').click();" title="Инвертировать все значения на этой странице">Switch All</a>',
            'value'          => function ($model) {
                return Html::tag('small', 'Parse',
                    [
                        'data-id' => $model->id,
                        'class'   => $model->status ? 'cat-status label label-primary' : 'cat-status label label-white',
                        'style'   => 'cursor: pointer; float: none; box-shadow: none; font-size: 11px; line-height: 2',
                        'title'   => 'ВНИМАНИЕ! Все связанные категории (по ресурсам) будут включены/выключены в парсинг',
                        'onclick' => '
                                $.get(\'index.php?r=category/status-update&id=' . $model->id . '\')
                                    .done( function (data) {
                                        catRow = $(\'.cat-status[data-id=' . $model->id . ']\');
                                        if (data == 0) catRow.removeClass(\'label-primary\').addClass(\'label-white\');
                                        else catRow.removeClass(\'label-white\').addClass(\'label-primary\');
                                    });
                            ',
                    ]
                );
            },
            'contentOptions' => [
                'style' => 'width: 90px; text-align: center',
            ],
        ],

        [
            'class'    => 'yii\grid\ActionColumn',
            'template' => '{view} {update}',
        ],
    ],
]);?>
</div>
