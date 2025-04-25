<?php


namespace app\assets;

use yii\web\AssetBundle;


class ParserTrialAsset extends AssetBundle
{
    // public $basePath = '@webroot';
    // public $baseUrl = '@web';
    public $css = [
    ];
    public $js = [
        'js/parser.js',
    ];
    public $depends = [
        'yii\web\YiiAsset',
        'yii\bootstrap\BootstrapAsset',
    ];
}
