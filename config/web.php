<?php

$params = require __DIR__ . '/params.php';
$db = require __DIR__ . '/db.php';

$config = [
    'id' => 'basic',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log'],
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm' => '@vendor/npm-asset',
    ],
    'modules' => [
        // 'modules' => [
        'api' => 'app\modules\Module',
        // ],
    ],
    'components' => [
        'request' => [
            // !!! insert a secret key in the following (if it is empty) - this is required by cookie validation
            'cookieValidationKey' => 'skAmqq5WGQFDANSeDbuH_PbPc5kUQK_6',
            'parsers' => [
                'application/json' => 'yii\web\JsonParser',
            ],
        ],
        'cache' => [
            'class' => 'yii\caching\FileCache',
        ],
        'user' => [
            'identityClass' => 'app\modules\models\User',
            'enableAutoLogin' => false,
        ],
        'errorHandler' => [
            'errorAction' => 'site/error',
        ],

        'mailer' => [
            'class' => 'yii\swiftmailer\Mailer',
            'useFileTransport' => false,
            'transport' => [
                'class' => 'Swift_SmtpTransport',
                'encryption' => 'tls',
                'host' => 'smtp.gmail.com',
                'port' => '587',
                'username' => 'thanhtoan28740@gmail.com',
                'password' => 'fhpu sbba nuay besb',
            ],
        ],

        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],
        'db' => $db,
        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'rules' => [
                // '<controller:\w+>/<action:\w+>/<id:\d+>' => '<controller>/<action:\w>',
                // 'DELETE user/deletebatch/<user_id>' => 'user/delete-batch',
                // 'PUT user/updatebatch/<user_id>' => 'user/update-batch',
                // 'POST category/delete/<categories_id>' => 'category/delete',
                // 'POST product/update/<product_id>' => 'product/update',
                // // 'api/user/login' => 'user/login',
                // // 'POST api/user/login' => 'api/user-login',

            ],
        ],
        'authManager' => [
            'class' => 'yii\rbac\DbManager',
        ],

        'weather' => [
            'class' => 'app\components\WeatherComponent',
            'apiKey' => '10da2c1fa4fd45b196141841241007',
        ],

    ],
    'params' => $params,
];

// Config alias for folder 'common'
Yii::setAlias('@common', dirname(__DIR__) . '/common');

if (YII_ENV_DEV) {
    // configuration adjustments for 'dev' environment
    $config['bootstrap'][] = 'debug';
    $config['modules']['debug'] = [
        'class' => 'yii\debug\Module',
        // uncomment the following to add your IP if you are not connecting from localhost.
        //'allowedIPs' => ['127.0.0.1', '::1'],
    ];

    $config['bootstrap'][] = 'gii';
    $config['modules']['gii'] = [
        'class' => 'yii\gii\Module',
        // uncomment the following to add your IP if you are not connecting from localhost.
        //'allowedIPs' => ['127.0.0.1', '::1'],
    ];
}

return $config;