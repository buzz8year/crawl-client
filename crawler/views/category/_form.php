<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use crawler\models\category\CategoryTags;

/* @var $this yii\web\View */
/* @var $model crawler\models\Category */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="category-form">

    <?php $form = ActiveForm::begin(); ?>

    <div class="row"><br/><br/>

	    <div class="col-sm-2">
	    	<?= $form->field($model, 'category_outer_id')->textInput() ?>
	    </div>

	    <div class="col-sm-6">
	    	<?= $form->field($model, 'title')->textInput(['maxlength' => true]) ?>
	    </div>

        <div class="col-sm-4 row pull-right">

            <label class="pull-left col-sm-12">Tags</label>

            <?php foreach (explode('+', $model->tags ?? '') as $tagId) 
            {
                $tag = CategoryTags::findOne($tagId);
                echo '<div class="pull-left col-sm-10">';

                if ($tag) echo Html::input('text', 'tags[]', $tag->tag, [
                    'data-id' => $tag->id, 
                    'class' => 'form-control', 
                    'readonly' => true
                ]) . '<br/>';
                echo '</div>';
                
                echo '<div class="col-sm-1">
                    <i class="btn btn-danger glyphicon glyphicon-trash header-value-remove" onclick=""></i>
                </div>';
            }
            ?>
            <?php echo '<div class="pull-left col-sm-12">';
                echo Html::input('text', 'tags[]', '', [
                    'class' => 'form-control',
                    'placeholder' => '...начните писать',
                    'oninput' => '',
                ]) . '<br/>'; 
                echo '</div>';
            ?>
        </div>

    </div><br/><br/>

    <div class="form-group">
        <?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
