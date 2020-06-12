<?php

namespace App;

use Symfony\Component\Process\Process;

/**
 * @author Thomas Pellissier Tanon
 * @copyright 2017-2019 Thomas Pellissier Tanon
 * @license GPL-2.0-or-later
 */

/**
 * provide data about fonts
 */
class FontProvider {

	/** @var mixed[]|null */
	protected static $fontData;

	protected static function getCacheFilename(): string {
		return FileCache::singleton()->getDirectory() . '/fonts.sphp';
	}

	/**
	 * Return data about a font.
	 * @param string $id Font family name.
	 * @return string[]|null Array of style names (e.g. Bold) to font filenames. Or null if no info found.
	 */
	public static function getData( string $id ) {
		if ( $id === '' ) {
			return null;
		}
		$cacheFilename = static::getCacheFilename();
		if ( !file_exists( $cacheFilename ) ) {
			static::buildFontData();
		}
		if ( static::$fontData === null ) {
			static::$fontData = unserialize( file_get_contents( $cacheFilename ) );
		}
		return static::$fontData[$id] ?? null;
	}

	/**
	 * Cache a list of locally installed fonts.
	 */
	protected static function buildFontData(): void {
		global $wsexportConfig;
		if ( !isset( $wsexportConfig['fonts'] ) ) {
			return;
		}

		$fontData = [];
		foreach ( $wsexportConfig['fonts'] as $font => $label ) {
			if ( !trim( $font ) ) {
				// Skip empty font label.
				continue;
			}
			// Get semicolon-separated parts of font information.
			$cmd = 'fc-list :family="' . $font . '" --format "%{file};%{family};%{style}\n"';
			$process = Process::fromShellCommandline( $cmd );
			$process->mustRun();
			$output = $process->getOutput();

			// Format the output into a single array.
			$lines = array_filter( explode( "\n", $output ) );
			foreach ( $lines as $line ) {
				$parts = str_getcsv( $line, ';' );
				if ( count( $parts ) !== 3 ) {
					// Guard against malformed results.
					continue;
				}
				$file = $parts[0];
				$family = $parts[1];
				$style = $parts[2];
				if ( !isset( $fontData[$family] ) ) {
					$fontData[$family] = [];
				}
				$fontData[$family][$style] = $file;
			}
		}

		// Save the font data. This will be unserialized in Util::getFontData().
		// Only save if there is any, in case something went wrong with getting the data.
		if ( count( $fontData ) > 0 ) {
			file_put_contents( static::getCacheFilename(), serialize( $fontData ) );
		}
	}

	/**
	 * Get the full list of available fonts.
	 * @return string[]
	 */
	public static function getList() {
		global $wsexportConfig;
		return $wsexportConfig['fonts'] ?? [];
	}

	/**
	 * Get CSS for the given font.
	 * @param string $id The font ID as defined in this class.
	 * @return string
	 */
	public static function getCss( string $id ): string {
		$font = static::getData( $id );
		if ( !$font ) {
			return '';
		}
		$css = '';
		foreach ( $font as $style => $file ) {
			// @todo These checks for bold and italic might need to be improved,
			// because $style can contain the style names in multiple langauges
			// and there might be one that has one of these strings but where it doesn't mean what it does in English.
			$fontWeight = stripos( $style, 'bold' ) !== false ? 'bold' : 'normal';
			$fontStyle = stripos( $style, 'italic' ) !== false ? 'italic' : 'normal';
			$css .= "@font-face {"
				. '  font-family: "' . $id . '";'
				. '  font-weight: ' . $fontWeight . ';'
				. '  font-style: ' . $fontStyle . ';'
				. '  src: url("fonts/' . basename( $file ) . '");'
				. "}\n";
		}
		$css .= 'body { font-family: "' . $id . '" }' . "\n";
		return $css;
	}
}
