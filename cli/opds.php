#!/usr/bin/php
<?php

$basePath = realpath( __DIR__ . '/..' );
$tempPath = sys_get_temp_dir();

$wsexportConfig = [
	'basePath' => $basePath, 'tempPath' => $tempPath, 'stat' => true
];
include_once $basePath . '/book/init.php';

$lang = 'fr';
$category = 'Catégorie:Bon_pour_export';
$outputFile = 'wikisource-fr-good.atom';
$exportPath = 'https://tools.wmflabs.org/wsexport/tool/book.php';

try {
	$api = new Api( $lang );
	$provider = new BookProvider( $api, [ 'categories' => false, 'images' => false ] );

	$atomGenerator = new OpdsBuilder( $provider, $lang, $exportPath );
	file_put_contents( $outputFile, $atomGenerator->buildFromCategory( $category ) );

	echo "The OPDS file has been created: $outputFile\n";
} catch ( Exception $exception ) {
	echo "Error: $exception\n";
}
