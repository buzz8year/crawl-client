<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model backend\models\Category */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="category-form">

    <?php $form = ActiveForm::begin(); ?>

    <div class="row"><br/><br/>
	    <div class="col-sm-6">
	    	<?= $form->field($model, 'category_outer_id')->textInput() ?>
	    </div>

	    <div class="col-sm-6">
	    	<?= $form->field($model, 'title')->textInput(['maxlength' => true]) ?>
	    </div>
    </div><br/><br/>

    <div class="form-group">
        <?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
