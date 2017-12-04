<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\ActiveForm;
use backend\models\Attribute;
use backend\models\Category;
use backend\models\Keyword;
use backend\models\Product;
use backend\models\Source;

/* @var $this yii\web\View */
/* @var $model backend\models\Product */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="product-form">

    <?php $form = ActiveForm::begin(); ?>


    <div class="row">

        <div class="col-lg-6">

            <?= $form->field($model, 'category_id')->dropDownList(
                Category::listCategories(),
                [
                    'prompt' => 'Без категории',
                ]
            ) 
            ?>
            <?= $form->field($model, 'price')->textInput() ?>
            <?= $form->field($model, 'price_new')->textInput() ?>

            <?= $form->field($model, 'track_price')->radioList( Product::trackPriceStatusText(), [
                    'item' => function ($index, $label, $name, $checked, $value) {
                        return '<label class="radio-inline">' . Html::radio($name, $checked, ['value'  => $value]) . $label . '</label>';
                    }
                ] ) ?>

            <?= $form->field($model, 'price_update')->textInput(['disabled' => true]) ?><br><br>

            <div><?php foreach ($model->images as $image) {
                
                    if (!$image['self_parent_id']) {
                        echo Html::a(Html::img($image['source_url'], ['height' => 75]), Url::to($image['source_url'], true), ['target' => '_blank']);
                    }
                } ?>
            </div><br>

            <div><?php foreach ($model->images as $image) {
                    if ($image['self_parent_id']) {
                        echo Html::a(Html::img($image['source_url']), Url::to($image['source_url'], true), ['target' => '_blank']);
                    }
                } ?>
            </div><br><br><br>


            <div><?php foreach ($model->attributeValues as $attribute) {
                    echo '<strong>' . $attribute['title'] . '</strong>: ' . $attribute['value'] . '<br>';
                } ?>
            </div><br><br><br><br>
x


            <div class="form-group">
                <?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
            </div>

        </div>

        <div class="col-lg-6">
            <?= $form->field($model, 'source_url')->textInput(['readonly' => true]) ?>
            <?= $form->field($model, 'source_id')->dropDownList( Source::listSources() ) ?>
            <?//= $form->field($model, 'keyword_id')->textInput() ?>
            <?= Html::activeLabel($model, 'keyword_id') ?>
            <?php
            if ($keyword = Keyword::findOne($model->keyword_id)) {
                echo Html::textInput('keyword', $keyword->word, ['class' => 'form-control', 'readonly' => true]);
            }
            ?>
            <?php foreach ($model->descriptions as $description) {
                echo '<strong>' . $description['title'] . '</strong>' . '<br>';
                echo $description['text_original'] . '<br><br>';
            } ?>

        </div>

    </div><br/>


    <?//= $form->field($model, 'is_rescheduled')->textInput() ?>


    <?php ActiveForm::end(); ?>

</div>
