<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model backend\models\Proxy */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="proxy-form"><br/><br/>

    <?php $form = ActiveForm::begin(); ?>

    <?= $form->field($model, 'ip')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'port')->textInput() ?>

    <?= $form->field($model, 'version')->dropDownList(['4' => 'IPv4', '6' => 'IPv6']) ?>

    <?= $form->field($model, 'login')->textInput() ?>

    <?= $form->field($model, 'password')->textInput() ?>

    <br/><br/>
    <div class="form-group">
        <?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
