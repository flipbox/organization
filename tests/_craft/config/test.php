<?php

use craft\helpers\ArrayHelper;
use craft\services\Config;

$_SERVER['REMOTE_ADDR'] = '1.1.1.1';
$_SERVER['REMOTE_PORT'] = 654321;

putenv("TEST_DB_NAME=craft3test");
putenv("TEST_DB_PASS=vagrant");
putenv("TEST_DB_USER=vagrant");

$basePath = dirname(dirname(dirname(__DIR__)));

$vendorPath = $basePath.'/vendor';
$srcPath = $vendorPath.'/craftcms/cms/src';

// Load the config
$config = ArrayHelper::merge(
    [
        'components' => [
            'config' => [
                'class' => Config::class,
                'configDir' => __DIR__,
                'appDefaultsDir' => $srcPath.'/config/defaults',
            ],
        ],
    ],
    require $srcPath.'/config/main.php',
    require $srcPath.'/config/common.php',
    require $srcPath.'/config/web.php'
);

$config['vendorPath'] = $vendorPath;

$config = ArrayHelper::merge($config, [
    'components' => [
        'sites' => [
            'currentSite' => 'default'
        ]
    ],
]);

return ArrayHelper::merge($config, [
    'class' => craft\web\Application::class,
    'id'=>'craft-test',
    'basePath' => $srcPath
]);
