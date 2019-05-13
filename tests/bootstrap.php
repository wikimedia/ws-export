<?php
$basePath = realpath( __DIR__ . '/..' );

global $wsexportConfig;
$wsexportConfig = [
	'basePath' => $basePath, 'stat' => false, 'tempPath' => sys_get_temp_dir(), 'debug' => false
];

require_once dirname( __DIR__ ) . '/vendor/autoload.php';
