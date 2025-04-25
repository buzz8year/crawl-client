<?php

use yii\helpers\Html;
use yii\grid\GridView;


$this->title = 'Proxies';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="proxy-index">

    <h1><?= Html::encode($this->title) ?></h1>
    <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

    <p> <?= Html::a('Create Proxy', ['create'], ['class' => 'btn btn-success']) ?> </p><br/><br/>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],

            'ip',
            'port',
            'version',

            [
                'class' => 'yii\grid\ActionColumn',
                'template' => '{view} {update}',
            ],
        ],
    ]); ?>
</div>
