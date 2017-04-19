<?php
$wsexportConfig = [
	'basePath' => '..', 'tempPath' => __DIR__ . '/../temp', 'stat' => true
];

include_once __DIR__  . '/../book/init.php';

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
	} else {
		$creator = BookCreator::forApi( $api, $format, $options );
		try {
			list( $book, $file ) = $creator->create( $title );
			header( 'X-Robots-Tag: none' );
			header( 'Content-Description: File Transfer' );
			header( 'Content-Type: ' . $creator->getMimeType() );
			header( 'Content-Disposition: attachment; filename="' . $title . '.' . $creator->getExtension() . '"' );
			header( 'Content-length: ' . filesize( $file ) );
			readfile( $file );
			flush();
			CreationLog::singleton()->add( $book, $format );
		} catch ( WSExportInvalidArgumentException $exception ) {
			throw new HttpException( 'Unsupported Media Type', 415 );
		} finally {
			if ( isset( $file ) ) {
				unlink( realpath( $file ) );
			}
		}

	}
} catch ( Exception $exception ) {
	if ( $exception instanceof HttpException ) {
		header( 'HTTP/1.1 ' . $exception->getCode() . ' ' . $exception->getMessage() );
	}
	$error = htmlspecialchars( $exception->getMessage() );
	include 'templates/book.php';
}
