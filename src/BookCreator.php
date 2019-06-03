<?php

namespace App;

use App\Generator\FormatGenerator;
use App\Util\Api;
use Exception;

/**
 * @license GPL-2.0-or-later
 */
class BookCreator {
	private $bookProvider;
	private $bookGenerator;

	public static function forApi( Api $api, $format, $options ) {
		return new BookCreator(
			new BookProvider( $api, $options ),
			GeneratorSelector::select( $format )
		);
	}

	public static function forLanguage( $language, $format, $options ) {
		return new BookCreator(
			new BookProvider( new Api( $language ), $options ),
			GeneratorSelector::select( $format )
		);
	}

	public function __construct( BookProvider $bookProvider, FormatGenerator $bookGenerator ) {
		$this->bookProvider = $bookProvider;
		$this->bookGenerator = $bookGenerator;
	}

	public function create( $title, $outputPath = null ) {
		date_default_timezone_set( 'UTC' );

		$book = $this->bookProvider->get( $title );
		$file = $this->bookGenerator->create( $book );
		if ( $outputPath ) {
			return [ $book, $this->renameFile( $book->title, $file, $outputPath ) ];
		} else {
			return [ $book, $file ];
		}
	}

	public function getMimeType() {
		return $this->bookGenerator->getMimeType();
	}

	public function getExtension() {
		return $this->bookGenerator->getExtension();
	}

	private function renameFile( $title, $file, $outputPath ) {
		$output = $outputPath . '/' . $title . '.' . $this->getExtension();
		if ( !is_dir( dirname( $output ) ) ) {
			mkdir( dirname( $output ), 0755, true );
		}
		if ( !rename( $file, $output ) ) {
			throw new Exception( 'Unable to create output file: ' . $output );
		}
		return $output;
	}
}
