<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model backend\models\Description */

$this->title = 'Update Description: {nameAttribute}';
$this->params['breadcrumbs'][] = ['label' => 'Descriptions', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->title, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = 'Update';
?>
<div class="description-update">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
