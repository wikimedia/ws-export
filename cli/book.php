#!/usr/bin/php
<?php

$basePath = realpath( dirname( __FILE__ ) . '/..' );

if( !isset( $_SERVER['argc'] ) || $_SERVER['argc'] < 3 ) {
	echo file_get_contents( $basePath . '/cli/help/book.txt' );
} else {
	$long_opts = array(
		'lang::', 'title::', 'format:', 'path::', 'debug', 'tmpdir:',
	);

	$lang = null;
	$title = null;
	$format = 'epub';
	$path = './';
	$tempPath = sys_get_temp_dir();

	$opts = getopt( 'l:t:f::p::d', $long_opts );
	foreach( $opts as $opt => $value ) {
		switch( $opt ) {
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
				if( !$tempPath ) {
					echo "Error: $value does not exist.\n";
				}
				break;
			case 'debug':
			case 'd':
				error_reporting( E_STRICT | E_ALL );
				break;
		}
	}
	if( !$lang or !$title or !$tempPath ) {
		echo file_get_contents( $basePath . '/cli/help/book.txt' );
		exit( 1 );
	}

	$wsexportConfig = array(
		'basePath' => $basePath, 'tempPath' => $tempPath, 'stat' => true
	);
	include_once( $basePath . '/book/init.php' );

	try {
		$api = new Api( $lang );
		$options = array();
		$options['images'] = true;
		$provider = new BookProvider( $api, $options );
		$data = $provider->get( $title );
		if( $format == 'epub-2' ) {
			$generator = new Epub2Generator();
		} elseif( $format == 'epub-3' || $format == 'epub' ) {
			$generator = new Epub3Generator();
		} elseif( in_array( $format, ConvertGenerator::getSupportedTypes() ) ) {
			$generator = new ConvertGenerator( $format );
		} elseif( $format == 'xhtml' ) {
			$generator = new XhtmlGenerator();
		} else {
			throw new Exception( "The file format $format is unknown" );
		}
		$file = $generator->create( $data );
		$path .= $title . '.' . $generator->getExtension();
		if( !is_dir( dirname( $path ) ) ) {
			mkdir( dirname( $path ), 0755, true );
		}
		if( $fp = fopen( $path, 'w' ) ) {
			fputs( $fp, $file );
		} else {
			error_log( 'Unable to create output file: ' . $path . "\n" );
			exit( 1 );
		}
		echo "The ebook has been created: $path\n";
	} catch( Exception $exception ) {
		echo "Error: $exception\n";
	}
}
