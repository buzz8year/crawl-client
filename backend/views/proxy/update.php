<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model backend\models\Proxy */

$this->title = 'Редактирование прокси: ' . $model->ip;
$this->params['breadcrumbs'][] = ['label' => 'Прокси', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->ip, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = 'Редактирование';
?>

<div class="row">
    <div class="col-lg-8">
        <h1><?= Html::encode($this->title) ?></h1>
    </div>
    <div class="col-lg-4 text-right" style="padding-top: 25px">
        <span><?php echo Html::a('Сделать глобальным', ['proxy/assign-global', 'id' => $model->id], ['class' => 'btn btn-danger']); ?></span>
    </div>
</div>


<div class="proxy-update">

    <?php if (Yii::$app->session->hasFlash('proxy-global')): ?><br/>
        <div class="alert alert-success alert-dismissable">
            <button aria-hidden="true" data-dismiss="alert" class="close" type="button">×</button>
            <?= Yii::$app->session->getFlash('proxy-global') ?>
        </div>
    <?php endif; ?>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
