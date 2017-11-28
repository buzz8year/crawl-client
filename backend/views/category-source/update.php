<?php

use yii\helpers\Html;
use backend\models\Category;

/* @var $this yii\web\View */
/* @var $model backend\models\CategorySource */

$this->title = 'Update Source Category: ' . Category::findOne($model->category_id)->title;
$this->params['breadcrumbs'][] = ['label' => 'Category Sources', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->id, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = 'Update';
?>
<div class="category-source-update">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
