<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\ActiveForm;
use crawler\models\attribute\Attribute;
use crawler\models\category\Category;
use crawler\models\keyword\Keyword;
use crawler\models\product\Product;
use crawler\models\source\Source;

/* @var $this yii\web\View */
/* @var $model crawler\models\Product */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="product-form">

    <!-- <div>
        <?php
        // echo Html::a(
        //     'Выгрузить в магазин', 
        //     ['product/sync-product', 'id' => $model->id],
        //     ['class' => 'btn btn-success']
        // ) 
        ?>
    </div><br/><br/><br/> -->

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

            <?= $form->field($model, 'sync_status')->radioList( Product::rescheduledStatusText(), [
                    'item' => function ($index, $label, $name, $checked, $value) {
                        return '<label class="radio-inline">' . Html::radio($name, $checked, ['value'  => $value]) . $label . '</label>';
                    }
            ] ) ?><br>

            <?= $form->field($model, 'track_price')->radioList( Product::trackPriceStatusText(), [
                    'item' => function ($index, $label, $name, $checked, $value) {
                        return '<label class="radio-inline">' . Html::radio($name, $checked, ['value'  => $value]) . $label . '</label>';
                    }
            ] ) ?><br>


            <div><label>Images:</label><br/><br/>
                <?php foreach ($model->images as $image) {
                    if (!$image['self_parent_id']) {
                        echo Html::a(
                            Html::img($image['source_url'], ['height' => 75]), 
                            Url::to($image['source_url'], true), 
                            [
                                'target' => '_blank',
                                'class' => 'bg-success',
                                'style' => 'padding: 5px; display: inline-block'
                            ]
                        );
                    }
                } ?>
            </div><br>

            <div><label>Thumbs:</label><br/><br/>
                <?php foreach ($model->images as $image) {
                    if ($image['self_parent_id']) {
                        echo Html::a(
                            Html::img($image['source_url'], ['height' => 35]), 
                            Url::to($image['source_url'], true), 
                            [
                                'target' => '_blank',
                                'class' => 'bg-success',
                                'style' => 'padding: 5px; display: inline-block'
                            ]
                        );
                    }
                } ?>
            </div><br><br><br>


            <div>
                <label>Attributes:</label><br/><br/>
                <?php foreach ($model->attributeValues as $attribute) {
                    echo '<strong>' . $attribute['title'] . '</strong>: ' . $attribute['value'] . '<br>';
                } ?>
            </div><br><br><br><br>


            <div class="form-group">
                <?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
            </div>

        </div>

        <div class="col-lg-6">
            <?= $form->field($model, 'source_url')->textInput(['readonly' => true]) ?>
            <?= $form->field($model, 'source_id')->dropDownList( Source::listSources() ) ?>
            <?= $form->field($model, 'keyword_id')->textInput() ?>
            <?//= Html::activeLabel($model, 'keyword_id') ?>
            <?= $form->field($model, 'price_update')->textInput(['disabled' => true]) ?><br><br>

            <?php
            if ($keyword = Keyword::findOne($model->keyword_id)) {
                echo Html::textInput('keyword', $keyword->word, ['class' => 'form-control', 'readonly' => true]);
            }
            ?>

            <label>Descriptions:</label><br/>
            <?php foreach ($model->descriptions as $description) {
                echo '<strong>' . $description['title'] . '</strong>' . '<br>';
                echo $description['text_original'] . '<br><br>';
            } ?>

        </div>

    </div><br/>

    <?php ActiveForm::end(); ?>



</div>
