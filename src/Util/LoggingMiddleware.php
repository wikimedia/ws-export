<?php

namespace App\Util;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

class LoggingMiddleware {
	/** @var LoggerInterface */
	private $logger;
	private $nextHandler;

	public static function forLogger( LoggerInterface $logger ) {
		return function ( callable $handler ) use ( $logger ) {
			return new LoggingMiddleware( $logger, $handler );
		};
	}

	public function __construct( LoggerInterface $logger, callable $nextHandler ) {
		$this->logger = $logger;
		$this->nextHandler = $nextHandler;
	}

	public function __invoke( RequestInterface $request, array $options ) {
		$this->logger->debug( $request->getMethod() . ' ' . $request->getUri() );
		$fn = $this->nextHandler;
		return $fn( $request, $options )->then( function ( ResponseInterface $response ) {
			if ( $response->getStatusCode() < 200 || $response->getStatusCode() > 299 ) {
				$this->logger->warning( 'HTTP response ' . $response->getStatusCode() );
			}
			return $response;
		} );
	}
}
