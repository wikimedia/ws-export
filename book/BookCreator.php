<?php

/**
 * @licence http://www.gnu.org/licenses/gpl.html GNU General Public Licence
 */
class BookCreator {
	private $bookProvider;
	private $bookGenerator;

	static function forApi( Api $api, $format, $options ) {
		return new BookCreator(
			new BookProvider( $api, $options ),
			GeneratorSelector::select( $format )
		);
	}

	static function forLanguage( $language, $format, $options ) {
		return new BookCreator(
			new BookProvider( new Api( $language ), $options ),
			GeneratorSelector::select( $format )
		);
	}

	public function __construct( BookProvider $bookProvider, FormatGenerator $bookGenerator ) {
		$this->bookProvider = $bookProvider;
		$this->bookGenerator = $bookGenerator;
	}

	function create( $title, $outputPath = null ) {
		date_default_timezone_set( 'UTC' );

		$book = $this->bookProvider->get( $title );
		$file = $this->bookGenerator->create( $book );
		if ( $outputPath ) {
			return [ $book, $this->renameFile( $book->title, $file, $outputPath ) ];
		} else {
			return [ $book, $file ];
		}
	}

	function getMimeType() {
		return $this->bookGenerator->getMimeType();
	}

	function getExtension() {
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
