<?php

class WSExportInvalidArgumentException extends Exception
{
}

class GeneratorSelector {
	static function select( $format ) {
		if ( $format == 'odt' ) {
			$format = 'rtf'; // TODO: bad hack in order to don't break urls
		}

		if ( $format == 'epub-2' ) {
			return new Epub2Generator();
		} elseif ( $format == 'epub-3' || $format == 'epub' ) {
			return new Epub3Generator();
		} elseif ( in_array( $format, ConvertGenerator::getSupportedTypes() ) ) {
			return new ConvertGenerator( $format );
		} elseif ( $format == 'atom' ) {
			return new AtomGenerator();
		} else {
			throw new WSExportInvalidArgumentException( "The file format '$format' is unknown." );
		}
	}
}
