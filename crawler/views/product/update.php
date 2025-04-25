<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model crawler\models\Product */

$this->title = $model->title;
$this->params['breadcrumbs'][] = ['label' => 'Products', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->id, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = 'Update';
?>
<div class="product-update">
	<br/>
    <h1><?= Html::encode($this->title) ?></h1><br/><br/>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
