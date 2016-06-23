<?php
$basePath = realpath( __DIR__ . '/..' );
global $wsexportConfig;

$wsexportConfig = [
'basePath' => $basePath, 'stat' => true, 'tempPath' => sys_get_temp_dir(), 'debug' => false
];

include_once $basePath . '/book/init.php';
