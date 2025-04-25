<?php

namespace crawler\assets;

use yii\web\AssetBundle;

/**
 * Main crawler application asset bundle.
 */
class AppAsset extends AssetBundle
{
    // public $basePath = '@webroot';
    // public $baseUrl = '@web';
    public $css = [
        'css/site.css',
        'css/font.css',
    ];
    public $js = [
    ];
    public $depends = [
        'yii\web\YiiAsset',
        'yii\bootstrap\BootstrapAsset',
    ];
}
