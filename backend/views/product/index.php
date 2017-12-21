<?php

use yii\helpers\Html;
use yii\grid\GridView;
use backend\models\Source;
use backend\models\Category;
use backend\models\Keyword;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $searchModel backend\models\search\ProductSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Products';
$this->params['breadcrumbs'][] = $this->title;
?>

<style type="text/css">
.container {
    min-width: 98%;
}
</style>

<div class="product-index">

    <h1><?= Html::encode($this->title) ?></h1>
    <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

    <div class="form-group pull-right text-right">
        <?= Html::a(
            'Удалить все товары', 
            ['product/delete-all'],
            [
                'class' => 'btn btn-danger', 
                'name' => 'deleteGoods', 
                'value' => 1,
                'onclick' => 'if (!confirm(\'Еще раз, удалить все товары?\')) { return false; }'
            ]
        ) ?>
    </div>

    <div class="form-group pull-right text-right">
        <?= Html::a(
            'Delete Misfits from OC', 
            ['product/delete-misfits'],
            [
                'class' => 'btn btn-danger', 
                'name' => 'deleteMisfits', 
                'value' => 1,
                'onclick' => 'if (!confirm(\'Удалить товары из ОС, которые отсутствуют в Yii?\')) { return false; }'
            ]
        ) ?>  &nbsp;  &nbsp;
    </div>

    <div class="form-group pull-right text-right">
        <?php ActiveForm::begin();?>
            <?= Html::submitButton('Выгрузить все товары в магазин', ['class' => 'btn btn-primary', 'name' => 'syncGoods', 'value' => 1]) ?>  &nbsp;  &nbsp;
        <?php ActiveForm::end();?>
        <?php if ($syncData) : ?>
            <span style="position:relative;top:20px">Обработано: <b><?= $syncData['processed'] ?></b>, Синхронизировано: <b><?= $syncData['synced'] ?></b></span><br/>
        <?php endif; ?>
    </div>

    <div class="form-group pull-right text-right">
        <span class="btn">В магазине сейчас товаров: <strong><?= number_format($ocProducts, 0, '', ',') ?></strong></span>  &nbsp;  &nbsp;
    </div>



    <?= Html::a('Create Product', ['create'], ['class' => 'btn btn-success']) ?><br/><br/>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'tableOptions' => [
            'class' => 'table table-striped'
        ],
        'columns' => [
            // ['class' => 'yii\grid\SerialColumn'],

            'id',
            [
                'format'    => 'raw',
                'label'    => 'Image',
                'value'     => function ($model) {
                    if ($model->images) {
                        return Html::img($model->images[0]['source_url'], ['height' => 20]);
                    }
                },
            ],
            // 'source_word_id',
            [
                'attribute' => 'title',
                'format'    => 'raw',
                'value'     => function ($model) {
                    return Html::tag('span', $model->title,
                        [
                            'target' => '_blank', 
                            'style' => 'display:block; max-width:20vw; overflow: hidden',
                            'title' => $model->title
                        ]
                    );
                },
            ],
            // 'category_id',
            [
                'attribute' => 'source_id',
                'format'    => 'raw',
                'filter' => Source::listSources(),
                'value'     => function ($model) {
                    return Html::a(Source::findOne($model->source_id)->title, ['source/index', 'SourceSearch[id]' => $model->source_id]);
                },
            ],
            [
                'attribute' => 'category_id',
                'format'    => 'raw',
                'value'     => function ($model) {
                    if ($category = Category::findOne($model->category_id)) {
                        return Html::a($category->title, ['category/index', 'CategorySearch[id]' => $model->category_id]);
                    }
                },
            ],
            [
                'label'     => 'Top Category',
                'content'   => function ($model) {
                    if (isset($model->topCategory)) {
                        return $model->topCategory->title;
                    }
                },
                'contentOptions' => [
                    'style' => 'width: 350px; white-space: normal',
                ],
            ],
            [
                'attribute' => 'keyword_id',
                'value'     => function ($model) {
                    if ($keyword = Keyword::findOne($model->keyword_id)) {
                        return $keyword->word;
                    }
                },
            ],
            [
                'attribute' => 'source_url',
                'format'    => 'raw',
                'value'     => function ($model) {
                    return Html::a($model->source_url, $model->source_url, 
                        [
                            'target' => '_blank', 
                            'style' => 'display:block; max-width:20vw; overflow: hidden',
                            'title' => $model->source_url
                        ]
                    );
                },
            ],
            'price',
            'price_new',
            //'price_new_last_update',
            //'track_price',
            'sync_status',
            'date_created',

            ['class' => 'yii\grid\ActionColumn'],
        ],
    ]); ?>
</div>
