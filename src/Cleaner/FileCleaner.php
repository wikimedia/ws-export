<?php

namespace App\Cleaner;

use Imagick;

class FileCleaner {
	public static function cleanFile( string $fileName, string $mimetype ) {
		if ( $mimetype === 'image/jpeg' ) {
			 self::cleanJPG( $fileName );
		}
	}

	private static function cleanJPG( string $fileName ) {
		$imagic = new Imagick( $fileName );
		if ( $imagic->getImageDepth() === 8 ) {
			// Some PDF readers do not like 8bits images
			$imagic->setImageDepth( 32 );
		}
		$imagic->writeImage( $fileName );
	}
}
