<?php

$params = require __DIR__ . '/params.php';
$db = require __DIR__ . '/db.php';

$config = [
    'id' => 'basic-console',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log', 'queue'],

    'controllerNamespace' => 'app\commands',
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm' => '@vendor/npm-asset',
        '@tests' => '@app/tests',
    ],
    'components' => [
        'cache' => [
            'class' => 'yii\caching\FileCache',
        ],
        'log' => [
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning', 'info'],
                ],
            ],
        ],
        'db' => $db,

        'authManager' => [
            'class' => 'yii\rbac\DbManager',
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
        'queue' => [
//            'class' => \yii\queue\file\Queue::class,
//            'path' => '@runtime/queue',
            'class' => \yii\queue\db\Queue::class,
            'db' => 'db',
            'tableName' => '{{%queue}}',
            'channel' => 'default',
            'mutex' => \yii\mutex\MysqlMutex::class,
        ],

//        'imap' => [
//            'class' => 'roopz\imap\Imap',
//            'connection' => [
//                'imapPath' => '{imap.gmail.com:993/imap/ssl}INBOX',
//                'imapLogin' => 'thanhtoan28740@gmail.com',
//                'imapPassword' => 'kcsh vptx hmim sbpn',
//                'serverEncoding' => 'utf-8',
//                'attachmentsDir' => __DIR__ . '/../attachments',
//                'decodeMimeStr' => true
//            ]
//        ],

    ],
    'params' => $params,
    /*
    'controllerMap' => [
        'fixture' => [ // Fixture generation command line.
            'class' => 'yii\faker\FixtureController',
        ],
    ],
    */
    'controllerMap' => [
        // 'migrate' => [
        //     'class' => 'yii\console\controllers\MigrateController',
        //     'interactive' => false,
        //     'migrationPath' => [
        //         '@app/migrations', // path to your custom migrations directory
        //     ],

        // ],
        'batch' => [
            'class' => 'schmunk42\giiant\commands\BatchController',
            'interactive' => false,
            'overwrite' => true,
            'skipTables' => ['system_db_migration', 'system_rbac_migration', 'migration'],
            'modelNamespace' => 'app\models',
            'crudTidyOutput' => false,
            'useTranslatableBehavior' => true,
            'useTimestampBehavior' => true,
            'enableI18N' => false,
            'modelQueryNamespace' => 'app\models\query',
            'modelBaseClass' => yii\db\ActiveRecord::class,
            'modelQueryBaseClass' => yii\db\ActiveQuery::class
        ],
//        'migrate' => [
//            'class' => 'yii\console\controllers\MigrateController',
//            'migrationPath' => [
//                '@vendor/sharkom/yii2-cron/migrations', // Đường dẫn tuyệt đối đến thư mục migrations
//            ],
//        ],
        //        'batch' => [
        //            'class' => 'schmunk42\giiant\commands\BatchController',
        //            'skipTables' => ['system_db_migration', 'system_rbac_migration', 'migration'],
        //            'overwrite' => true,
        //            'interactive' => false,
        //            'modelNamespace' => 'app\models',
        //        ]
    ],
];

if (YII_ENV_DEV) {
    // configuration adjustments for 'dev' environment
    $config['bootstrap'][] = 'gii';
    $config['modules']['gii'] = [
        'class' => 'yii\gii\Module',
    ];
    // configuration adjustments for 'dev' environment
    // requires version `2.1.21` of yii2-debug module
    $config['bootstrap'][] = 'debug';
    $config['modules']['debug'] = [
        'class' => 'yii\debug\Module',
        // uncomment the following to add your IP if you are not connecting from localhost.
        //'allowedIPs' => ['127.0.0.1', '::1'],
    ];
}

return $config;
