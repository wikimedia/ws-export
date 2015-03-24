#!/usr/bin/php
<?php

$basePath = realpath( __DIR__ . '/..' );
$tempPath = sys_get_temp_dir();

$wsexportConfig = array(
	'basePath' => $basePath, 'tempPath' => $tempPath, 'stat' => true
);
include_once( $basePath . '/book/init.php' );

$lang = 'fr';
$category = 'Catégorie:Bon_pour_export';
$outputFile = 'wikisource-fr-good.atom';
$exportPath = 'http://wsexport.wmflabs.org/tool/book.php';

try {
	$api = new Api( $lang );
	$provider = new BookProvider( $api, array( 'categories' => false, 'images' => false ) );

	$atomGenerator = new OpdsBuilder( $provider, $lang, $exportPath );
	$file = file_put_contents( $outputFile, $atomGenerator->buildFromCategory( $category ) );

	echo "The OPDS file has been created: $outputFile\n";
} catch( Exception $exception ) {
	echo "Error: $exception\n";
}
