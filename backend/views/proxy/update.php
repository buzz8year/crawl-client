<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model backend\models\Proxy */

$this->title = 'Update Proxy: ' . $model->ip;
$this->params['breadcrumbs'][] = ['label' => 'Proxies', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->id, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = 'Update';
?>
<div class="proxy-update">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
