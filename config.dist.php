<?php

return [
	'debug' => false,
	'stat' => true,
	'basePath' => __DIR__,
	'tempPath' => __DIR__ . '/temp/',
	'exec-timeout' => 120,

	'logDatabase' => __DIR__ . '/public/logs.sqlite',
	'dbDsn' => 'mysql:host=127.0.0.1;dbname=DBNAME;charset=utf8',
	'dbUser' => 'DBUSERNAME',
	'dbPass' => 'DBPASSWORD',

	// 'ebook-convert' => '',
];
