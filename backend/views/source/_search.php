<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model backend\models\search\SourceSearch */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="source-search">

    <?php $form = ActiveForm::begin([
        'action' => ['index'],
        'method' => 'get',
    ]); ?>

    <?= $form->field($model, 'id') ?>

    <?= $form->field($model, 'title') ?>

    <?= $form->field($model, 'source_url') ?>

    <?= $form->field($model, 'description') ?>

    <?= $form->field($model, 'class_namespace') ?>

    <?php // echo $form->field($model, 'need_synonymizer') ?>

    <?php // echo $form->field($model, 'need_proxy') ?>

    <?php // echo $form->field($model, 'need_captcha') ?>

    <div class="form-group">
        <?= Html::submitButton('Search', ['class' => 'btn btn-primary']) ?>
        <?= Html::resetButton('Reset', ['class' => 'btn btn-default']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
