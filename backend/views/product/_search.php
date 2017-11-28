<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model backend\models\search\ProductSearch */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="product-search">

    <?php $form = ActiveForm::begin([
        'action' => ['index'],
        'method' => 'get',
    ]); ?>

    <?= $form->field($model, 'id') ?>

    <?= $form->field($model, 'source_id') ?>

    <?= $form->field($model, 'keyword_id') ?>

    <?= $form->field($model, 'category_id') ?>

    <?= $form->field($model, 'source_url') ?>

    <?php // echo $form->field($model, 'price') ?>

    <?php // echo $form->field($model, 'price_new') ?>

    <?php // echo $form->field($model, 'price_new_last_update') ?>

    <?php // echo $form->field($model, 'track_price') ?>

    <?php // echo $form->field($model, 'is_rescheduled') ?>

    <div class="form-group">
        <?= Html::submitButton('Search', ['class' => 'btn btn-primary']) ?>
        <?= Html::resetButton('Reset', ['class' => 'btn btn-default']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
