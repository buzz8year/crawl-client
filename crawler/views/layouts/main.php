<?php

/** @var \yii\web\View $this */
/** @var string $content  */

use crawler\assets\AppAsset;
use yii\helpers\Html;
use yii\bootstrap\Nav;
use yii\bootstrap\NavBar;
use yii\widgets\Breadcrumbs;
use common\widgets\Alert;

AppAsset::register($this);

?>

<?php $this->beginPage() ?>

<!DOCTYPE html>

<html lang="<?= Yii::$app->language ?>">

<head>
    <meta charset="<?= Yii::$app->charset ?>">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?= Html::csrfMetaTags() ?>
    <title><?= Html::encode($this->title) ?></title>
    <?php $this->head() ?>
</head>

<body>
<?php $this->beginBody() ?>

<div class="wrap">

    <?php

    NavBar::begin([
        // 'brandLabel' => Yii::$app->name,
        // 'brandUrl' => Yii::$app->homeUrl,
        'options' => [
            'class' => 'navbar-inverse navbar-fixed-top',
        ],
    ]);

    $menuItems = [
        // ['label' => 'Home', 'url' => ['/site/index']],
        ['label' => 'PARSE', 'url' => ['/parser/index'], 'active' => in_array(Yii::$app->controller->id, ['parser']) ],
        ['label' => 'History', 'url' => ['/history/index'], 'active' => in_array(Yii::$app->controller->id, ['history', 'options-log']) ],
        ['label' => 'Products', 'url' => ['/product/index'], 'active' => in_array(Yii::$app->controller->id, ['product'])],
        ['label' => 'Categories', 'url' => ['/category/index'], 'active' => in_array(Yii::$app->controller->id, ['category'])],
        // ['label' => 'Categories', 'url' => ['/category/index', 'CategorySearch[status]' => 1]],
        ['label' => 'Relations', 'url' => ['/category-source/index'], 'active' => in_array(Yii::$app->controller->id, ['category-source'])],
        ['label' => 'Sources', 'url' => ['/source/index'], 'active' => in_array(Yii::$app->controller->id, ['source'])],
        ['label' => 'Attributes', 'url' => ['/attribute/index'], 'active' => in_array(Yii::$app->controller->id, ['attribute'])],
        // ['label' => 'Keywords', 'url' => ['/keyword/index']],
        ['label' => 'Proxies', 'url' => ['/proxy/index'], 'active' => in_array(Yii::$app->controller->id, ['proxy'])],
        ['label' => 'Headers', 'url' => ['/header/index'], 'active' => in_array(Yii::$app->controller->id, ['header'])],
    ];

    if (Yii::$app->user->isGuest)
        $signItems[] = ['label' => 'Login', 'url' => ['/site/login']];

    else {
        $signItems[] = 
            '<li class="row">'
                . Html::beginForm(['/site/logout'], 'post')
                . Html::submitButton(
                    'Logout (' . Yii::$app->user->identity->username . ')',
                    ['class' => 'btn btn-link logout']
                )
                . Html::endForm()
            . '</li>';
    }

    echo Nav::widget([
        'options' => ['class' => 'navbar-nav navbar-left'],
        'items' => $signItems,
    ]);

    echo Nav::widget([
        'options' => ['class' => 'navbar-nav navbar-right'],
        'items' => $menuItems,
    ]);

    NavBar::end();

    ?>

    <div class="container">
        <?= Breadcrumbs::widget([
            'links' => isset($this->params['breadcrumbs']) ? $this->params['breadcrumbs'] : [],
        ]) ?>
        <?= Alert::widget() ?>
        <?= $content ?>
    </div>

</div>

<footer class="footer">
    <div class="container">
        <p class="pull-left">&copy; <?= Html::encode(ucfirst(Yii::$app->name)) ?> <?= date('Y') ?></p>
    </div>
</footer>

<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
