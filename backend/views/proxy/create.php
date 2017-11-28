<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model backend\models\Proxy */

$this->title = 'Create Proxy';
$this->params['breadcrumbs'][] = ['label' => 'Proxies', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="proxy-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
