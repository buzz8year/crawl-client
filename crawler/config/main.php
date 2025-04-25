<?php
$params = array_merge(
    require __DIR__ . '/../../common/config/params.php',
    require __DIR__ . '/../../common/config/params-local.php',
    require __DIR__ . '/params.php',
    require __DIR__ . '/params-local.php'
);

return [
    'id' => 'app-crawler',
    'basePath' => dirname(__DIR__),
    'controllerNamespace' => 'crawler\controllers',
    'bootstrap' => ['log'],
    // 'modules' => [
    //     'webshell' => [
    //         'class' => 'samdark\webshell\Module',
    //         'yiiScript' => __DIR__ . '/../../yii', // adjust path to point to your ./yii script
    //         // 'yiiScript' => Yii::getAlias('@root'). '/yii', // adjust path to point to your ./yii script
    //     ],
    // ],
    'components' => [
        // 'cache' => [
        //     'class' => 'yii\caching\MemCache',
        //     'useMemcached' => true,
        //     'servers' => [
        //         [
        //             'host' => 'loaclhost',
        //             'port' => 11211,
        //             'weight' => 60,
        //         ],
        //     ],
        // ],
        'cache' => [ 
            'class' => 'yii\caching\DbCache',
        ],
        'request' => [
            'csrfParam' => '_csrf-crawler',
        ],
        'user' => [
            'identityClass' => 'common\models\user\User',
            'enableAutoLogin' => false,
            'identityCookie' => ['name' => '_identity-crawler', 'httpOnly' => true],
        ],
        'session' => [
            // this is the name of the session cookie used for login on the crawler
            'name' => 'advanced-crawler',
        ],
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    // 'db' => '',
                    'class' => 'yii\log\FileTarget',
                    // 'class' => 'yii\log\DbTarget',
                    // 'levels' => ['error', 'warning', 'trace'],
                    // 'maskVars' => [
                    //     '_POST.LoginForm.password',
                    //     '_POST.SignupForm.password',
                    // ],
                ],
            ],
        ],
        'errorHandler' => [
            'errorAction' => YII_DEBUG ? null : 'site/error',
            // 'errorAction' => 'site/error',
        ],

        // 'urlManager' => [
        //     'enablePrettyUrl' => true,
        //     'showScriptName' => false,
        //     'rules' => [],
        // ],
    ],
    'params' => $params,
];
