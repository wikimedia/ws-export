<?php

class FileCleaner {

	/**
	 * @param string $mimetype
	 * @return bool whether files with the given MIME type need cleaning up
	 */
	public static function needsCleaning( string $mimetype ): bool {
		return $mimetype === 'image/svg+xml';
	}

	public static function cleanFile( string $content, string $mimetype ): string {
		if ( self::needsCleaning( $mimetype ) ) {
			return self::cleanSVG( $content );
		}
		return $content;
	}

	private static function cleanSVG( string $content ): string {
		return preg_replace( '/<!DOCTYPE[^>[]*(\[[^]]*\])?>/', '', $content );
	}
}
