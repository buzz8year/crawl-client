<?php
$params = array_merge(
    require __DIR__ . '/../../common/config/params.php',
    require __DIR__ . '/../../common/config/params-local.php',
    require __DIR__ . '/params.php',
    require __DIR__ . '/params-local.php'
);

return [
    'id' => 'app-console',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log'],
    'controllerNamespace' => 'console\controllers',
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm'   => '@vendor/npm-asset',
        '@morphy' => '@vendor/phpmorphy',
        '@phantom' => '@vendor/bin/phantomjs',
        // '@phantom' => '@vendor/bin/phantomjs.exe',
    ],
    'controllerMap' => [
        'fixture' => [
            'class' => 'yii\console\controllers\FixtureController',
            'namespace' => 'common\fixtures',
          ],
    ],
    'components' => [
        'log' => [
            // 'targets' => [
            //     [
            //         'class' => 'yii\log\FileTarget',
            //         'levels' => ['error', 'warning'],
            //     ],
            // ],
            'flushInterval' => 1,
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'logFile' => '@runtime/logs/parse.log',
                    'categories' => ['parse-console'],
                    'exportInterval' => 1,
                    'levels' => ['info'],
                    'logVars' => []
                ],
            ],
        ],
    ],
    'params' => $params,
];
