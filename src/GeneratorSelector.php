<?php

namespace App;

use App\Generator\AtomGenerator;
use App\Generator\ConvertGenerator;
use App\Generator\EpubGenerator;
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

	/** @var FontProvider */
	private $fontProvider;

	/** @var Api */
	private $api;

	public function __construct( FontProvider $fontProvider, Api $api ) {
		$this->fontProvider = $fontProvider;
		$this->api = $api;
	}

	public function getGenerator( $format ) {
		if ( $format === 'odt' ) {
			$format = 'rtf'; // TODO: bad hack in order to don't break urls
		}

		if ( $format === 'epub-3' || $format === 'epub' ) {
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
