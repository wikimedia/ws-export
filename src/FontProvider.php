<?php

namespace App;

use DateInterval;
use Symfony\Component\Process\Process;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * @author Thomas Pellissier Tanon
 * @copyright 2017-2019 Thomas Pellissier Tanon
 * @license GPL-2.0-or-later
 */

/**
 * provide data about fonts
 */
class FontProvider {

	/** @var CacheInterface */
	private $cache;

	/** @var string[][]|null */
	private $data;

	/** @var string[] Runtime cache of variants of font names. */
	private $names;

	public function __construct( CacheInterface $cache ) {
		$this->cache = $cache;
		$this->names = [];
	}

	/**
	 * Return data about a font.
	 * @param string|null $name Font family name.
	 * @return string[][]|null Array with 'styles' and 'langs' keys, the former containing style names (e.g. Bold) to
	 * font filenames and the latter an array of language codes supported by the font. Or null if no info found.
	 */
	public function getOne( ?string $name ): ?array {
		if ( $name === '' || $name === null ) {
			return null;
		}

		$name = $this->resolveName( $name );

		if ( isset( $this->data[ $name ] ) ) {
			return $this->data[ $name ];
		}

		$this->data = $this->getAll();

		return $this->data[ $name ] ?? null;
	}

	/**
	 * Get a list of all locally installed fonts.
	 * @return string[][][] Key is the font name, value is an array with 'styles' and 'langs' keys.
	 */
	private function getAll(): array {
		return $this->cache->get( 'fonts', function ( ItemInterface $item ) {
			$item->expiresAfter( new DateInterval( 'P7D' ) );
			$fontData = [];
			// Get semicolon-separated parts of font information.
			$cmd = 'fc-list --format "%{file};%{family};%{style};%{lang}\n"';
			$process = Process::fromShellCommandline( $cmd );
			$process->mustRun();
			$output = $process->getOutput();
			// Format the output into a single array.
			$lines = array_filter( explode( "\n", $output ) );
			foreach ( $lines as $line ) {
				$parts = str_getcsv( $line, ';' );
				if ( count( $parts ) !== 4 ) {
					// Guard against malformed results.
					continue;
				}
				$file = $parts[ 0 ];
				$family = $parts[ 1 ];
				$style = $parts[ 2 ];
				$langs = explode( '|', $parts[3] );
				if ( !isset( $fontData[ $family ] ) ) {
					$fontData[ $family ] = [
						'styles' => [],
						'langs' => $langs,
					];
				}
				$fontData[ $family ]['styles'][ $style ] = $file;
			}
			return $fontData;
		} );
	}

	/**
	 * Get all fonts that support the given language.
	 * @param string $lang
	 * @return string[][]
	 */
	public function getForLang( string $lang ): array {
		$out = [];
		foreach ( $this->getAll() as $font ) {
			if ( array_search( $lang, $font['langs'] ) !== false ) {
				$out[] = $font;
			}
		}
		return $out;
	}

	/**
	 * Get the full list of preferred fonts.
	 * @return string[]
	 */
	public function getPreferred( ?string $additional ): array {
		$fonts = [
			// Font family name => English label
			'FreeSerif' => 'FreeSerif', // Hard-coded default for non-latin scripts.
			'Linux Libertine O' => 'Linux Libertine',
			'Libertinus' => 'Libertinus',
			'Mukta' => 'Mukta (Devanagari)',
			'Mukta Mahee' => 'Mukta Mahee (Gurmukhi)',
			'Mukta Malar' => 'Mukta Malar (Tamil)',
			'Gubbi' => 'Gubbi (Kannada)',
		];
		$out = [];
		foreach ( $fonts as $name => $label ) {
			if ( $this->getOne( $name ) ) {
				$out[$name] = $label;
			}
		}
		$additionalFont = $this->resolveName( $additional );
		if ( $additionalFont ) {
			$out[$additionalFont] = $additionalFont;
		}
		return $out;
	}

	/**
	 * Turn a font family name into its canonical form (as its known by the OS).
	 * This will check lower- and snake-case variants.
	 * @param string|null $name
	 * @return string|bool The normalized name, or false if a matching font could not be found.
	 */
	public function resolveName( ?string $name ): string {
		if ( isset( $this->names[ $name ] ) ) {
			return $this->names[ $name ];
		}
		// Preserve backwards compatibility for an old font key.
		// https://github.com/wsexport/tool/blob/b4895ed5f986bb29dfc4b8663844c4145c4517bf/src/FontProvider.php#L25
		// The other legacy keys are all handled by the lowercase normalization below.
		if ( $name === 'linuxlibertine' ) {
			return 'Linux Libertine O';
		}
		foreach ( $this->getAll() as $fontName => $fontInfo ) {
			$names = [
				$fontName,
				strtolower( $fontName ),
				str_replace( ' ', '-', $fontName ),
				str_replace( ' ', '-', strtolower( $fontName ) ),
			];
			foreach ( $names as $n ) {
				if ( $n === $name ) {
					$this->names[ $name ] = $fontName;
					return $fontName;
				}
			}
		}
		return false;
	}

	/**
	 * Get CSS for the given font.
	 * @param string $name The font family name as defined in this class.
	 * @return string
	 */
	public function getCss( string $name ): string {
		$name = $this->resolveName( $name );
		if ( !$name ) {
			return '';
		}
		$font = $this->getOne( $name );
		$css = [];
		foreach ( $font['styles'] as $style => $file ) {
			// Set font weight.
			// Normal is usually 400 weight.
			$fontWeight = 'normal';
			if ( stripos( $style, 'semibold' ) !== false ) {
				$fontWeight = '500';
			} elseif ( stripos( $style, 'bold' ) !== false ) {
				// Bold is 700 weight.
				$fontWeight = 'bold';
			}
			// Set font style.
			$fontStyle = 'normal';
			if ( stripos( $style, 'italic' ) !== false ) {
				$fontStyle = 'italic';
			} elseif ( stripos( $style, 'oblique' ) !== false ) {
				$fontStyle = 'oblique';
			}
			if ( isset( $css[$name . $fontWeight . $fontStyle] ) ) {
				// Some font styles have multiples, so we only include the first one.
				continue;
			}
			$css[$name . $fontWeight . $fontStyle] = "@font-face {"
				. '  font-family: "' . $name . '";'
				. '  font-weight: ' . $fontWeight . ';'
				. '  font-style: ' . $fontStyle . ';'
				. '  src: url("fonts/' . basename( $file ) . '");'
				. '}';
		}
		$css[] = 'body { font-family: "' . $name . '" }';
		return implode( "\n", $css ) . "\n";
	}
}
