<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model backend\models\Source */

$this->title = 'Update Source: ' . $model->title;
$this->params['breadcrumbs'][] = ['label' => 'Sources', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->title, 'url' => ['parser/trial', 'id' => $model->id]];
$this->params['breadcrumbs'][] = 'Update';
?>

<div class="row">
    <div class="col-lg-6">
        <h1><?= Html::encode($this->title) ?></h1>
    </div>
    <div class="col-lg-12" style="padding-top: 25px">
        <span><?php echo Html::a('Удалить несинхр. (' . count($model->asyncProducts) . ') товары', ['source/delete-products', 'id' => $model->id, 'async' => 1], ['class' => 'btn btn-danger']); ?></span>
        <span><?php echo Html::a('Удалить все (' . count($model->products) . ') товары', ['source/delete-products', 'id' => $model->id], ['class' => 'btn btn-danger']); ?></span>
        <span><?php echo Html::a('Удалить все (' . count($model->categorySources) . ') категории', ['source/delete-categories', 'id' => $model->id], ['class' => 'btn btn-danger pull-right']); ?></span>
    </div>
</div>

<div class="source-update"><br/>

    <?php if (Yii::$app->session->hasFlash('source-update')): ?>
        <div class="alert alert-success alert-dismissable">
            <button aria-hidden="true" data-dismiss="alert" class="close" type="button">×</button>
            <?= Yii::$app->session->getFlash('source-update') ?>
        </div>
    <?php endif; ?>

    <?php if (Yii::$app->session->hasFlash('categories-deleted')): ?>
        <div class="alert alert-success alert-dismissable">
            <button aria-hidden="true" data-dismiss="alert" class="close" type="button">×</button>
            <?= Yii::$app->session->getFlash('categories-deleted') ?>
        </div>
    <?php endif; ?>

    <?php if (Yii::$app->session->hasFlash('products-deleted')): ?>
        <div class="alert alert-success alert-dismissable">
            <button aria-hidden="true" data-dismiss="alert" class="close" type="button">×</button>
            <?= Yii::$app->session->getFlash('products-deleted') ?>
        </div>
    <?php endif; ?>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
