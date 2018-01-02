<?php

use yii\helpers\Html;
use backend\models\Source;

// echo phpinfo();
?>

<div class="row">
	<?php 
		foreach ($sources as $id => $source)
		{
			echo '<div class=\'col-xs-4\' style=\'margin: 30px 0\'>';
			if ($source['status']) {
				echo Html::a($source['title'], [ 'parser/trial', 'id' => $id ], [ 'class' => 'text-uppercase' ]);
			} else {
				echo '<span class=\'text-uppercase text-danger\'>' . $source['title'] . '</span>';
			}
			echo '<br/><small>' . Html::a($source['domain'], $source['domain']) . '</small>';
			// echo '<br/><small class="text-muted">Категорий: ' . count(Source::findOne($id)->categorySources) . '</small>';
			echo '<br/><small class="text-muted">Категорий: ' . Source::findOne($id)->countCategorySources . '</small>';
			// echo '<br/><small class="text-muted">Товаров: ' . count(Source::findOne($id)->liteProducts) . '</small>';
			echo '<br/><small class="text-muted">Товаров: ' . Source::findOne($id)->countProducts . '</small>';
			// echo '<br/><small class="text-muted">Несинх.: ' . count(Source::findOne($id)->asyncProducts) . '</small>';
			echo '<br/><small class="text-muted">Несинх.: ' . Source::findOne($id)->countAsyncProducts . '</small>';
			echo '</div>';
		} 
	?>
</div>