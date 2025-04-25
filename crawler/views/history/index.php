<?php

use yii\helpers\Html;
use yii\grid\GridView;
use crawler\models\source\Source;
use crawler\models\keyword\Keyword;
use crawler\models\Category;
use crawler\models\CategorySource;

$this->title = 'History';

$this->params['breadcrumbs'][] = $this->title;
?>


<div class="row">
    <div class="col-lg-8">
        <h1><?= Html::encode($this->title) ?></h1>
    </div>
    <div class="col-lg-4 text-right">
        <h3>&nbsp;</h3>
        <span><?php echo Html::a('Лог проксей и заголовков', ['options-log/index']); ?></span>
    </div>
</div><br/><br/>

<div class="history-index">

    <?=GridView::widget([
    'dataProvider' => $dataProvider,
    // 'filterModel'  => $searchModel,
    'tableOptions' => [
        'class' => 'table table-striped',
    ],
    'rowOptions'   => function ($model) {
        $class = '';
        if ($model->status == 5 || $model->status == 9 || $model->status == 11) {
            $class = 'text-danger';
        }
        return [
            'class' => $class,
        ];
    },
    'columns'      => [
        'date',
        [
            'attribute' => 'url',
            'format'    => 'raw',
            'label'     => 'URL',
            'value'     => function ($model) {
                return Html::a($model->url, $model->url, 
                ['title' => $model->url, 'style' => 'width:15vw; display:block; overflow:hidden; text-overflow:ellipsis']);
            },
        ],
        // 'source_id',
        [
            'attribute' => 'source_id',
            'format'    => 'raw',
            'value'     => function ($model) {
                return Html::a(\crawler\models\source\Source::findOne($model->source_id)->title, ['source/index', 'SourceSearch[id]' => $model->source_id]);
            },
        ],
        // 'category_source_id',
        [
            'attribute' => 'category_source_id',
            'format'    => 'raw',
            'value'     => function ($model) {
                if ($category = \crawler\models\category\CategorySource::findOne($model->category_source_id)) {
                    return Html::a(\crawler\models\category\Category::findOne($category->category_id)->title, ['category/index', 'CategorySearch[id]' => $category->category_id]);
                }
            },
        ],
        // 'keyword_id',
        [
            'attribute' => 'keyword_id',
            'value'     => function ($model) {
                if ($keyword = \crawler\models\keyword\Keyword::findOne($model->keyword_id)) {
                    return $keyword->word;
                }
            },
        ],
        'proxy_source_id',
        'header_source_id',
        'item_quantity',
        'client',
        'time',
        'status',
        [
            'attribute' => 'note',
            'label'     => 'Note',
            'format'    => 'raw',
            'value'     => function ($model) {
                switch ($model->status) {
                case 0:
                    return '<span class=\'label label-warning\' title=\'' . $model->note . '\'>no results</span>';
                case 2:
                    return '<span class=\'label label-primary\' title=\'' . $model->note . '\'>max</span>';
                case 3:
                    return '<span class=\'label label-success\' title=\'' . $model->note . '\'>depreciable</span>';
                case 5:
                    return '<span class=\'label label-danger\' title=\'' . $model->note . '\'>warning</span>';
                case 9:
                    return '<span class=\'label label-danger\' title=\'' . $model->note . '\'>error</span>';
                case 10:
                    return '<span class=\'label label-white\' title=\'' . $model->note . '\'>regular</span>';
                case 11:
                    return '<span class=\'label label-danger\' title=\'' . $model->note . '\'>proxy</span>';
                case 12:
                    return '<span class=\'label label-danger\' title=\'' . $model->note . '\'>user-agent</span>';
                default:
                    return '<span class=\'label label-success\'>OK</span>';
                }
            },
        ],
    ],
]);
?>

</div>