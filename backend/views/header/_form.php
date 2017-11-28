<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

\app\assets\HeaderAsset::register($this);

?>

<div class="header-form"><br/><br/>

    <?php $form = ActiveForm::begin(); ?>

    <div class="row">

	    <div class="col-xs-4 text-capitalize">
	    	<?= $form->field($model, 'title')->textInput(['maxlength' => true]) ?>

	    	<?php if ($model->headerFullSources) :  ?>
	    	
	    		<label>Assigned to Sources:</label><br/>

	    		<small>
				    <?php 
				    $links = [];
				    foreach ($model->headerFullSources as $source)  {
		                $links[] = Html::a($source['title'], ['source/update', 'id' => $source['id']]);
				    }
				    echo implode(', ', $links);
				    ?>
		    	</small>

		    <?php endif; ?>
	    </div>

	    <div class="col-xs-8">

	    	<label style="display: block"><?= $model->title ?> Values:</label>

	        <?php if ($model->headerValues) :  ?>

			    <?php foreach ($model->headerValues as $value) : ?>

			    	<div class="row header-value" data-id="<?= $value['id'] ?>">
			            <div class="col-sm-11">
			                <input type="text" value="<?= $value['value'] ?>" name="header-old-values[<?= $value['id'] ?>][]" class="form-control">
			            </div>
			            <div class="col-sm-1">
                            <i class="btn btn-danger glyphicon glyphicon-trash region-remove" onclick="$.get('index.php?r=header/value-delete&id=<?= $value['id'] ?>').done(function(data){
                            	if(data == 'deleted') {
                            		$('.header-value[data-id=<?= $value['id'] ?>]').remove();
                            	} else {
                            		alert(data);
                            	}
                            });"></i>
                        </div><br/><br/><br/>
			        </div>

			    <?php endforeach; ?>
		    
		    <?php endif; ?>

        	<button class="btn btn-primary header-value-add" type="button"><i class="glyphicon glyphicon-plus"></i></button>

	    </div>

    </div><br/><br/>

    <div class="form-group">
        <?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
