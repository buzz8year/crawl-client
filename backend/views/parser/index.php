<?php

use yii\helpers\Html;

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
			echo '<br/><small>' . Html::a($source['domain'], $source['domain']) . '</small></div>';
		} 
	?>
</div>