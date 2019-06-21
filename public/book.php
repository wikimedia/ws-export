<?php

require_once dirname( __DIR__ ) . '/bootstrap.php';

use App\BookCreator;
use App\CreationLog;
use App\Exception\HttpException;
use App\Exception\WSExportInvalidArgumentException;
use App\Refresh;
use App\Util\Api;
use App\Util\Util;
use GuzzleHttp\Exception\RequestException;

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
	} elseif ( $title === '' ) {
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
				Util::removeFile( $file );
			}
		}

	}
} catch ( Exception $exception ) {
	$code = 500;
	$message = 'Internal Server Error';
	$error = $exception->getMessage();
	$doLog = true;
	if ( $exception instanceof HttpException ) {
		$parts = preg_split( '/[\r\n]+/', $exception->getMessage(), 2 );
		$code = $exception->getCode();
		$message = $parts[0];
		// 404's are quite popular, not logging them
		$doLog = $exception->getCode() !== 404;
	} elseif ( $exception instanceof RequestException ) {
		$response = $exception->getResponse();
		if ( $response ) {
			$error = Util::extractErrorMessage( $response, $exception->getRequest() ) ?: $error;
		}
	}
	if ( $doLog && !defined( 'IN_UNIT_TEST' ) ) {
		$stdout = fopen( 'php://stderr', 'w' );
		fputs( $stdout, Util::formatException( $exception ) );
	}

	header( "HTTP/1.1 $code $message" );
	$error = nl2br( htmlspecialchars( $error ) );
	include 'templates/book.php';
}
