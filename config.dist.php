<?php

return [
	'debug' => false,
	'stat' => PHP_SAPI !== 'cli',
	'basePath' => __DIR__,
	'tempPath' => __DIR__ . '/temp/',
	'logDatabase' => __DIR__ . '/http/logs.sqlite'
	// 'ebook-convert' => '',
];
