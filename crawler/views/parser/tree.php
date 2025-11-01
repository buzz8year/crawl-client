<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\widgets\Breadcrumbs;
use crawler\util\CategoryNestedPrinter;

/** @var \yii\web\View $this The view instance */

$this->title = 'Парсинг категорий ' . $model->title;
$this->params['newcrumbs'][] = ['label' => $model->title, 'url' => ['parser/trial', 'id' => $model->id]];
$this->params['newcrumbs'][] = 'Категории';

echo Breadcrumbs::widget([
    'homeLink' => ['label' => 'Парсинг', 'url' => 'index.php'],
    'links' => $this->params['newcrumbs'] ?? [],
]);

?>

<div class="row">
    <div class="col-lg-8">
        <h1>Category parsing for <?=$model->title?></h1>
        <small><?= Html::a($model->domain, $model->domain, ['target' => '_blank']) ?></small>
    </div>
    <div class="col-lg-4 pull-right">
        <h4>&nbsp;</h4>
        <span><?php echo Html::a('Редактировать', ['source/update', 'id' => $model->id]); ?></span>
        <span class="pull-right"><?php echo Html::a('К парсингу товаров', ['parser/trial', 'id' => $model->id]); ?></span>
    </div>
</div><br/><br/>



<div class="row">
	<div class="col-lg-12">

		<div class="form-group">
			<button data-toggle="collapse" data-target="#tree" class="btn btn-primary pull-right">Current categories</button>

			<?php ActiveForm::begin();?>
				<?= Html::submitButton('Parse Categories', ['class' => 'btn btn-success', 'name' => 'parseTree', 'value' => 1]) ?>
			<?php ActiveForm::end();?><br/><br/>

			<small>After parsing the categories, you will need to confirm saving them to the database.</small>
		</div><br/><br/>


		<div class="row">
			<div class="collapse col-xs-12" id="tree" style="column-count: 3">
				<?php foreach ($categories as $category) : ?>
					<div class="col-xs-12">
						<div class="row">
							<?php CategoryNestedPrinter::printNest($category); ?>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
		</div>


		<div class="row">

			<?php if (count($misses) && $parsedCategories): ?>

				<div class="col-lg-12"> <hr/> </div>

				<?php if ($misses['global']): ?>
					<div class="col-lg-6">
						<strong>Found new (global categories):</strong><br/>
						<span class="text-danger"><?=implode(', ', $misses['global'])?></span>
					</div>
				<?php endif;?>

				<?php if ($misses['source']): ?>
					<div class="col-lg-6">
						<strong>Found new (current resource categories):</strong><br/>
						<span class="text-danger"><?=implode(', ', $misses['source'])?></span>
					</div>
				<?php endif;?>

				<?php if (!$misses['global'] && !$misses['source']): ?>
					<div class="col-lg-6">
						<span class="text-success"><strong>Nothing found.</strong></span>
					</div>
				<?php endif;?>

			<?php endif;?>

			<?php if ($parsedCategories): ?>

				<div class="col-lg-12"><br/><br/>
					<button data-toggle="collapse" data-target="#results" class="btn btn-primary">Result</button>
				</div>

			<?php elseif ($error) : ?>
				<div class="col-lg-12"><br/><br/>
					<span class="text-danger"><strong>Algrithmic error or your IP is banned.</strong></span>
				</div>
			<?php endif;?>

		</div>

		<?php if ($parsedCategories): ?>

			<div class="row collapse" id="results">

				<div class="col-xs-12">
					<?php if (count($misses) && (count($misses['global']) || count($misses['source']))): ?>
						<br/><br/>
						<?php ActiveForm::begin();?>
							<?=Html::submitButton('Сохранить', ['class' => 'btn btn-success', 'name' => 'saveChanges', 'value' => json_encode($parsedCategories)])?>
						<?php ActiveForm::end();?>
					<?php endif;?>

					<br/><br/>
				</div>

				<div class="col-xs-12" style="column-count: 3">

					<?php foreach ($parsedCategories as $parsedCategory): ?>
						<div class="col-xs-12">
							<div class="row">
								<?php CategoryNestedPrinter::printNest($parsedCategory); ?>
							</div>
						</div>
					<?php endforeach;?>

				</div>

			</div>

		<?php endif;?>

	</div>

</div><br/><br/>
