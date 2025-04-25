<?php


namespace app\assets;

use yii\web\AssetBundle;


class HeaderAsset extends AssetBundle
{
    // public $basePath = '@webroot';
    // public $baseUrl = '@web';
    public $css = [
    ];
    public $js = [
        'js/header.js'
    ];
    public $depends = [
        'yii\web\YiiAsset',
        'yii\bootstrap\BootstrapAsset',
    ];
}
