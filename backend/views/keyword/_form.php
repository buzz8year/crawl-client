<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use backend\models\Keyword;
use backend\models\Source;

/* @var $this yii\web\View */
/* @var $model backend\models\Keyword */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="keyword-form"><br/><br/>

    <?php $form = ActiveForm::begin(); ?>

    <div class="row">
    	<div class="col-xs-12">
		    <div class="row">
		    	<div class="col-md-4">
			   		<?= $form->field($model, 'word')->textInput(['maxlength' => true]) ?>
			    </div>
		    </div>
	    </div>
    	<div class="col-md-8">
    		<label class="control-label" for="keyword-word">Sources</label><br/>
            <?php  
                echo Html::dropDownList('', '', Source::listSources(), [
                    'id' => 'categoryList',
                    'class' => 'form-control',
                    // 'options'   => Parser::urlSources($model->id),
                    'onchange'  => '$(\'#parseInput\').val( $(this).find(\':selected\').attr(\'data-url\') ).focus().blur();',
                    'prompt'    => '-- Select --'
                ]); 
            ?><br/>
    	</div>
    </div><br/><br/>

    <div class="form-group">
        <?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
