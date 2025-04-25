<?php


namespace app\assets;

use yii\web\AssetBundle;


class AttributeAsset extends AssetBundle
{
    // public $basePath = '@webroot';
    // public $baseUrl = '@web';
    public $css = [
    ];
    public $js = [
        'js/attribute.js'
    ];
    public $depends = [
        'yii\web\YiiAsset',
        'yii\bootstrap\BootstrapAsset',
    ];
}
