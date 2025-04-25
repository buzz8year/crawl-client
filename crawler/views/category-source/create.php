<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model crawler\models\CategorySource */

$this->title = 'Create Category Source';
$this->params['breadcrumbs'][] = ['label' => 'Category Sources', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="category-source-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
