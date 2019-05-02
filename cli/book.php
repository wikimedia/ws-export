#!/usr/bin/php
<?php

$basePath = realpath( __DIR__ . '/..' );
global $wsexportConfig;

$wsexportConfig = [
	'basePath' => $basePath, 'stat' => false, 'tempPath' => $basePath . '/temp', 'debug' => false
];

include_once $basePath . '/book/init.php';

function parseCommandLine() {
	global $wsexportConfig;

	$long_opts = [
		'lang:', 'title:', 'format:', 'path:', 'debug', 'tmpdir:',
		'nocredits'
	];

	$lang = null;
	$title = null;
	$format = 'epub';
	$path = '.';
	$options = [];
	$options['images'] = true;

	$opts = getopt( 'l:t:f:p:d', $long_opts );
	foreach ( $opts as $opt => $value ) {
		switch ( $opt ) {
			case 'lang':
			case 'l':
				$lang = $value;
				break;
			case 'title':
			case 't':
				$title = $value;
				break;
			case 'format':
			case 'f':
				$format = $value;
				break;
			case 'path':
			case 'p':
				$path = $value . '/';
				break;
			case 'tmpdir':
				$tempPath = realpath( $value );
				if ( !$tempPath ) {
					throw new Exception( "Error: $value does not exist." );
				}
				$wsexportConfig['tempPath'] = $tempPath;
				break;
			case 'debug':
			case 'd':
				$wsexportConfig['debug'] = true;
				error_reporting( E_STRICT | E_ALL );
				break;
			case 'nocredits':
				$options['credits'] = false;
				break;
		}
	}

	if ( !$lang || !$title ) {
		throw new WSExportInvalidArgumentException();
	}

	return [
		'title' => $title,
		'lang' => $lang,
		'format' => $format,
		'path' => $path,
		'options' => $options
	];
}

if ( isset( $argc ) ) {
	try {
		$arguments = parseCommandLine();
		$creator = BookCreator::forLanguage( $arguments['lang'], $arguments['format'], $arguments['options'] );
		list( $book, $file ) = $creator->create( $arguments['title'], $arguments['path'] );
		echo "The ebook has been created: $file\n";
	} catch ( WSExportInvalidArgumentException $exception ) {
		if ( !empty( $exception->getMessage() ) ) {
			fwrite( STDERR, $exception->getMessage() . "\n\n" );
		}
		fwrite( STDERR, file_get_contents( $basePath . '/cli/help/book.txt' ) );
		exit( 1 );
	} catch ( Exception $exception ) {
		fwrite( STDERR, "Error: $exception\n" );
		exit( 1 );
	}
}
