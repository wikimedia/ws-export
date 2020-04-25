<?php

namespace App\Cleaner;

use Imagick;
use ImagickDraw;
use ImagickPixel;

class FileCleaner {
	public static function cleanFile( string $fileName, string $mimetype ) {
		if ( $mimetype === 'image/jpeg' ) {
			 self::cleanJPG( $fileName );
		}
	}

	private static function cleanJPG( string $fileName ) {
		$imagic = new Imagick( $fileName );
		if ( $imagic->getImageColorspace() === Imagick::COLORSPACE_GRAY ) {
			// Some PDF readers do not like grayscale images
			$imagic->setImageColorspace( Imagick::COLORSPACE_SRGB );

			// Hack to add a not greyscale color to the image to make Imagic to save it as sRGB
			$draw = new ImagickDraw();
			$draw->setFillColor( new ImagickPixel( "red" ) );
			$draw->setfillopacity( 0.0001 );
			$draw->point( 0, 0 );
			$imagic->drawImage( $draw );

			$imagic->writeImage( $fileName );
		}
		$imagic->clear();
	}
}
