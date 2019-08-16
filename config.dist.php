<?php

return [
	'debug' => false,
	'stat' => true,
	'basePath' => __DIR__,
	'tempPath' => __DIR__ . '/temp/',
	'exec-timeout' => 120,

	'logDatabase' => __DIR__ . '/public/logs.sqlite',
	'dbDsn' => 'mysql:host=localhost;dbname=DBNAME;charset=utf8',
	'dbUser' => 'DBUSERNAME',
	'dbPass' => '',

	// 'ebook-convert' => '',
];
