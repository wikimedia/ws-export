<?php

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\NullHandler;

class ToolLogger {
	public static function get( $name ) {
		if ( static::isDebugEnabled() ) {
			return static::standardErrLogger( $name );
		} else {
			return static::nullLogger();
		}
	}

	private static function isDebugEnabled() {
		global $wsexportConfig;
		return !empty( $wsexportConfig['debug'] );
	}

	private static function standardErrLogger( $name ) {
		return new Logger( $name, [ new StreamHandler( 'php://stderr' ) ] );
	}

	private static function nullLogger() {
		return new Logger( 'null-logger', [ new NullHandler() ] );
	}
}
