<?php

class FileCleaner {

	public static function cleanFile( $content, $mimetype ) {
		if ( $mimetype === 'image/svg+xml' ) {
			return self::cleanSVG( $content );
		}
		return $content;
	}

	private static function cleanSVG( $content ) {
		return preg_replace( '/<!DOCTYPE[^>[]*(\[[^]]*\])?>/', '', $content );
	}
}
