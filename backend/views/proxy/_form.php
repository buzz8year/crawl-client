<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model backend\models\Proxy */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="proxy-form row"><br/><br/>

    <?php $form = ActiveForm::begin(); ?>

    <div class="col-sm-4">

    <?= $form->field($model, 'ip')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'port')->textInput() ?>

    </div>
    <div class="col-sm-4">


    <?= $form->field($model, 'login')->textInput() ?>
    <?= $form->field($model, 'password')->textInput() ?>
    </div>

    <div class="col-sm-4">
    <?= $form->field($model, 'version')->dropDownList(['4' => 'IPv4', '4' => 'IPv6']) ?>
    </div>

    <div class="form-group col-sm-12"><br/><br/>
        <?= Html::submitButton('Сохранить', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
