#!/usr/bin/php
<?php

if ( count( $argv ) < 2 ) {
	echo 'You should provide the output path like "wikisource-fr-good.atom"';
	exit( 1 );
}

require_once dirname( __DIR__ ) . '/bootstrap.php';

use App\BookProvider;
use App\OpdsBuilder;
use App\Util\Api;

$lang = 'fr';
$category = 'Catégorie:Bon_pour_export';
$outputFile = $argv[1];
$exportPath = 'https://wsexport.toolforge.org/tool/book.php';

try {
	date_default_timezone_set( 'UTC' );
	$api = new Api();
	$api->setLang( $lang );
	$provider = new BookProvider( $api, [ 'categories' => false, 'images' => false ] );

	$atomGenerator = new OpdsBuilder( $provider, $lang, $exportPath );
	file_put_contents( $outputFile, $atomGenerator->buildFromCategory( $category ) );

	echo "The OPDS file has been created: $outputFile\n";
} catch ( Exception $exception ) {
	echo "Error: $exception\n";
}
