<?php

use yii\helpers\Html;
use backend\models\Source;

?>

<div class="row">
	<?php 
		foreach ($sources as $id => $source)
		{
			echo '<div class=\'col-xs-4\' style=\'margin: 30px 0\'>';
			if ($source['status']) {
				echo '<b>' . Html::a($source['title'], [ 'parser/trial', 'id' => $id ], [ 'class' => 'text-uppercase' ]) . '</b>';
			} else {
				echo '<span class=\'text-uppercase text-danger\'>' . $source['title'] . '</span>';
			}
			echo '<br/><small>' . Html::a($source['domain'], $source['domain']) . '</small>';
			echo '<br/><small class="text-muted">Категорий: ' . count(Source::findOne($id)->categorySources) . '</small></div>';
		} 
	?>
</div>