<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model backend\models\Description */

$this->title = 'Create Description';
$this->params['breadcrumbs'][] = ['label' => 'Descriptions', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="description-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
