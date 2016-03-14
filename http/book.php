<?php
$wsexportConfig = [
	'basePath' => '..', 'tempPath' => __DIR__ . '/../temp', 'stat' => true
];

include_once '../book/init.php';

$api = new Api();
$title = isset( $_GET['page'] ) ? trim( htmlspecialchars( urldecode( $_GET['page'] ) ) ) : '';
$format = isset( $_GET['format'] ) ? htmlspecialchars( urldecode( $_GET['format'] ) ) : 'epub';
$options = [];
$options['images'] = isset( $_GET['images'] ) ? filter_var( $_GET['images'], FILTER_VALIDATE_BOOLEAN ) : true;
if ( in_array( $api->lang, [ 'fr', 'en', 'de', 'it', 'es', 'pt', 'vec', 'pl', 'nl', 'fa', 'he', 'ar' ] ) ) {
	$options['fonts'] = isset( $_GET['fonts'] ) ? strtolower( htmlspecialchars( urldecode( $_GET['fonts'] ) ) ) : '';
} else {
	$options['fonts'] = isset( $_GET['fonts'] ) ? strtolower( htmlspecialchars( urldecode( $_GET['fonts'] ) ) ) : 'freeserif';
	if ( filter_var( $options['fonts'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE ) === false ) {
		$options['fonts'] = '';
	}
}

try {
	if ( isset( $_GET['refresh'] ) ) {
		$refresh = new Refresh( $api );
		$refresh->refresh();
		$success = 'The cache is updated for ' . $api->lang . ' language.';
		include 'templates/book.php';
	}

	if ( $title === '' ) {
		include 'templates/book.php';
	}

	$provider = new BookProvider( $api, $options );
	$data = $provider->get( $title );
	if ( $format == 'epub' ) {
		$format = 'epub-3';
	} elseif( $format == 'odt' ) {
		$format = 'rtf'; // TODO: bad hack in order to don't break urls
	}

	if ( $format == 'epub-2' ) {
		$generator = new Epub2Generator();
	} elseif( $format == 'epub-3' ) {
		$generator = new Epub3Generator();
	} elseif( in_array( $format, ConvertGenerator::getSupportedTypes() ) ) {
		$generator = new ConvertGenerator( $format );
	} elseif( $format == 'atom' ) {
		$generator = new AtomGenerator();
	} else {
		throw new HttpException( 'Unsupported Media Type', 415 );
	}

	$file = $generator->create( $data );
	header( 'X-Robots-Tag: none' );
	header( 'Content-Description: File Transfer' );
	header( 'Content-Type: ' . $generator->getMimeType() );
	header( 'Content-Disposition: attachment; filename="' . $title . '.' . $generator->getExtension() . '"' );
	header( 'Content-length: ' . filesize( $file ) );
	readfile( $file );
	unlink( $file );
	flush();
	if ( isset( $wsexportConfig['stat'] ) ) {
		CreationLog::singleton()->add( $data, $format );
	}
} catch ( Exception $exception ) {
	if ( $exception instanceof HttpException ) {
		header( 'HTTP/1.1 ' . $exception->getCode() . ' ' . $exception->getMessage() );
	}
	$error = htmlspecialchars( $exception->getMessage() );
	include 'templates/book.php';
}
