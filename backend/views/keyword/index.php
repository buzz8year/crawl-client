<?php

use yii\helpers\Html;
use yii\grid\GridView;
use yii\helpers\ArrayHelper;
use \backend\models\Source;
use \backend\models\Keyword;

/* @var $this yii\web\View */
/* @var $searchModel backend\models\search\KeywordSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Keywords';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="keyword-index">

    <h1><?= Html::encode($this->title) ?></h1><br/>
    <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

    <p> <?= Html::a('Create Keyword', ['create'], ['class' => 'btn btn-success']) ?> </p><br/>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],

            'word',

            ['class' => 'yii\grid\ActionColumn'],
        ],
    ]); ?>
</div>
