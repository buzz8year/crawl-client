<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model backend\models\Keyword */

$this->title = 'Create Keyword';
$this->params['breadcrumbs'][] = ['label' => 'Keywords', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="keyword-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
