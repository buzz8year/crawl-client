<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use crawler\models\category\Category;
use crawler\models\source\Source;

/* @var $this yii\web\View */
/* @var $model crawler\models\CategorySource */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="category-source-form"><br/><br/>

    <?php $form = ActiveForm::begin(); ?>

    <div class="row">
	    <div class="col-lg-6">
	        <?= $form->field($model, 'category_id')->dropDownList(Category::listCategories(), ['prompt'    => '-- Category --']) ?>
	    </div>
	    <div class="col-lg-6">
	        <?= $form->field($model, 'source_id')->dropDownList(Source::listSources(), ['prompt'    => '-- Source --']) ?>
	    </div>
    </div>

    <div class="row">
        <div class="col-lg-6">
            <?= $form->field($model, 'source_url')->textInput() ?>
            <p class="text-muted">Cигнатура ссылки<br/>
                <small>http://www.ozon.ru/catalog/1162422/</small>
            </p>
        </div>

        <div class="col-lg-6">
            <?= $form->field($model, 'source_url_dump')->textInput() ?>
            <p class="text-muted">Что есть разгрузка ?
                <small> - любые аргументы, всем скопом<br/>http://www.ozon.ru/catalog/1162422/<em class="text-primary">?store=1,2&catalog_select=123467,1234568</em></small>
            </p>
        </div>

        <div class="col-lg-6">
            <?= $form->field($model, 'source_category_alias')->textInput() ?>
            <p class="text-muted"> 
                http://www.ozon.ru/context/<b>div_appliance</b>/<br/>
                <small><em>жирным обозначено, может отсутствовать. Необходим, если на ресурсе при <br/>поиске возможно передавать `alias` категории как аргумент - <br/>напр.: <span class="text-primary">/search?text=ларь&category_alias=<b>div_appliance</b></span></em></small>
            </p>
        </div>
        <div class="col-lg-6">
            <?= $form->field($model, 'source_category_id')->textInput() ?>
            <p class="text-muted"> 
                https://www.books.ru/nauka-tekhnika-meditsina-<b>9000660</b>/<br/>
                <small><em>жирным обозначено, может отсутствовать. Необходим, если на ресурсе при <br/>поиске возможно передавать `id` категории как аргумент - <br/>напр.: <span class="text-primary">/search?text=радиотехника&category_id=<b>9000660</b></span></em></small>
            </p>
        </div>
    </div>


    <br/><br/>

    <div class="form-group">
        <?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
