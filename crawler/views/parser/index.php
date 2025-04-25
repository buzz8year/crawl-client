<?php

use yii\helpers\Html;

?>

<style>
.alert-vendor {
	margin: 30px 0;
}
.label-icon {
	cursor: pointer;
	/* background-color: #fff;
	color: #444!important; */
}
</style>

<div class="row">
	<?php 
		foreach ($sources as $id => $source)
		{
			echo '<div class="col-xs-4 alert alert-vendor">';
			
			if ($source['status']) {
				$fa = '<span class="label label-primary label-icon">new</span> &nbsp; ';
				$link = Html::a($source['title'], [ 'parser/trial', 'id' => $id ], [ 'class' => 'text-uppercase' ]);
				echo $fa . $link;
			} else {
				echo '<span class=\'text-uppercase text-danger\'>' . $source['title'] . '</span>';
			}
			echo '<div style="margin-top:20px">' . Html::a($source['domain'], $source['domain']) . '</div>';
			echo '<small class="text-muted">Категорий: ' . \crawler\models\source\Source::findOne($id)->countCategorySources . '</small>';
			echo '<br/><small class="text-muted">Товаров: ' . \crawler\models\source\Source::findOne($id)->countProducts . '</small>';
			echo '<br/><small class="text-muted">Несинх.: ' . \crawler\models\source\Source::findOne($id)->countAsyncProducts . '</small>';

			echo '</div>';
		} 
	?>
</div>