<?php

namespace App;

use App\Generator\AtomGenerator;
use App\Generator\ConvertGenerator;
use App\Generator\EpubGenerator;
use App\Generator\FormatGenerator;
use App\Util\Api;
use Exception;

class GeneratorSelector {

	public static $formats = [
		'epub-3' 	=> 'epub 3',
		'epub-2'	=> 'epub 2 (deprecated, may be useful for some very old e-readers)',
		'htmlz'		=> 'htmlz (zip archive with an html file inside, in beta)',
		'mobi'		=> 'mobi (in beta)',
		'pdf-a4'	=> 'pdf A4 format (in beta)',
		'pdf-a5'	=> 'pdf A5 format (in beta)',
		'pdf-a6'	=> 'pdf A6 format (in beta)',
		'pdf-letter' => 'pdf US letter format (in beta)',
		'rtf' 		=> 'rtf (in beta)',
		'txt'		=> 'txt (in beta)'
	];

	/** @var string[] Format aliases. */
	private static $aliases = [
		'epub' => 'epub-3',
		// Returning RTF for ODT is a hack in order to not break existing URLs.
		'odt' => 'rtf',
	];

	/** @var FontProvider */
	private $fontProvider;

	/** @var Api */
	private $api;

	public function __construct( FontProvider $fontProvider, Api $api ) {
		$this->fontProvider = $fontProvider;
		$this->api = $api;
	}

	/**
	 * @return string[] All format names (including aliases).
	 */
	public static function getAllFormats(): array {
		return array_merge( array_keys( self::$formats ), array_keys( self::$aliases ) );
	}

	public function getGenerator( $format ): FormatGenerator {
		// Resolve alias.
		if ( array_key_exists( $format, self::$aliases ) ) {
			$format = self::$aliases[$format];
		}
		if ( $format === 'epub-3' ) {
			return new EpubGenerator( $this->fontProvider, $this->api );
		} elseif ( in_array( $format, ConvertGenerator::getSupportedTypes() ) ) {
			return new ConvertGenerator( $format, $this->fontProvider, $this->api );
		} elseif ( $format === 'atom' ) {
			return new AtomGenerator();
		} else {
			throw new Exception( "The file format '$format' is unknown." );
		}
	}
}
