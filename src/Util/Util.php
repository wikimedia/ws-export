<?php

namespace App\Util;

use DOMDocument;
use DOMElement;
use DOMXPath;
use HtmlFormatter\HtmlFormatter;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Process\Process;

/**
 * @author Thomas Pellissier Tanon
 * @copyright 2011 Thomas Pellissier Tanon
 * @license GPL-2.0-or-later
 */

class Util {

	/**
	 * @param string $lang the language of the wiki
	 * @param string $page the name of the page
	 * @return string an url to a page of Wikisource
	 */
	public static function wikisourceUrl( $lang, $page = '' ) {
		if ( $lang === '' ) {
			$url = 'https://wikisource.org';
		} elseif ( $lang === 'beta' ) {
			$url = 'https://en.wikisource.beta.wmflabs.org';
		} else {
			$url = 'https://' . $lang . '.wikisource.org';
		}

		if ( $page !== '' ) {
			$url .= '/wiki/' . urlencode( $page );
		}

		return $url;
	}

	/**
	 * @param string $file the path to the file
	 * @return string the content of a file
	 */
	public static function getFile( $file ) {
		$content = '';
		$fp = fopen( $file, 'r' );
		if ( $fp ) {
			while ( !feof( $fp ) ) {
				$content .= fgets( $fp, 4096 );
			}
		}

		return $content;
	}

	/**
	 * get an xhtml page from a text content
	 * @param string $lang content language code
	 * @param string $content
	 * @param string $title
	 * @return string
	 */
	public static function getXhtmlFromContent( $lang, $content, $title = ' ' ) {
		$bodyPosition = stripos( $content, '<body' );
		// Remove all content before <body tag
		$content = substr( $content, $bodyPosition );

		if ( $content != '' ) {
			$content = preg_replace( '#<\!--(.+)-->#isU', '', $content );
		}
		$html = '<?xml version="1.0" encoding="UTF-8" ?><!DOCTYPE html><html xmlns="http://www.w3.org/1999/xhtml"';
		if ( $lang != null ) {
			$html .= ' xml:lang="' . $lang . '" dir="' . static::getLanguageDirection( $lang ) . '"';
		}
		$html .= '><head><meta content="application/xhtml+xml;charset=UTF-8" http-equiv="default-style" /><link type="text/css" rel="stylesheet" href="main.css" /><title>' . $title . '</title></head>';

		if ( $bodyPosition ) {
			return $html . $content;
		}
		return $html . '<body>' . $content . '</body></html>';
	}

	// Replace non-ASCII characters with ASCII equivalents.
	public static function encodeString( $string ) {
		static $map = [];
		static $num = 0;
		$string = str_replace( ' ', '_', $string );
		if ( isset( $map[$string] ) ) {
			return $map[$string];
		}
		$map[$string] = $string;
		$replacements = [
			'[αάΑΆàâäÀÂÄ]' => 'a',
			'[βΒ]' => 'b',
			'[ψΨçÇ]' => 'c',
			'[δΔ]' => 'd',
			'[εΕéèêëÉÈÊË]' => 'e',
			'[ηΗ]' => 'eh',
			'[φϕΦ]' => 'f',
			'[γΓ]' => 'g',
			'[θΘ]' => 'h',
			'[ιΙîïÎÏ]' => 'i',
			'[κΚ]' => 'k',
			'[λΛ]' => 'l',
			'[μΜ]' => 'm',
			'[νΝ]' => 'n',
			'[οΟôöÔÖ]' => 'o',
			'[ωΩ]' => 'oh',
			'[πΠ]' => 'p',
			'[ψΨ]' => 'ps',
			'[ρΡ]' => 'r',
			'[σΣ]' => 's',
			'[τΤ]' => 't',
			'[υΥûùüÛÜ]' => 'u',
			'[ξΞ]' => 'x',
			'[ζΖ]' => 'z',
			'[^a-zA-Z0-9_. ]' => '_'
		];
		mb_regex_encoding( 'UTF-8' );
		foreach ( $replacements as $search => $replace ) {
			$map[$string] = mb_eregi_replace( $search, $replace, $map[$string] );
		}
		$map[$string] = 'c' . $num . '_' . static::cutFilename( iconv( 'UTF-8', 'ISO-8859-1//TRANSLIT', $map[$string] ) );
		$num++;

		return $map[$string];
	}

	/**
	 * Cut a filename if it is too long but keep the extension
	 * @param string $string
	 * @param int $max
	 * @return string
	 */
	public static function cutFilename( string $string, int $max = 100 ): string {
		$length = strlen( $string );
		if ( $length > $max ) {
			$string = substr( $string, $length - $max, $length - 1 );
		}

		return $string;
	}

	/**
	 * @param string $languageCode
	 * @return string "rtl" or "ltr"
	 */
	public static function getLanguageDirection( $languageCode ) {
		return in_array( $languageCode, [ 'ar', 'arc', 'bcc', 'bqi', 'ckb', 'dv', 'fa', 'glk', 'he', 'lrc', 'mzn', 'pnb', 'ps', 'sd', 'ug', 'ur', 'yi' ] )
			? 'rtl'
			: 'ltr';
	}

	public static function removeFile( $fileName ) {
		$process = new Process( [ 'rm', realpath( $fileName ) ] );
		$process->mustRun();
	}

	/**
	 * Attempts to extract a string error message from the error response returned by the remote server
	 *
	 * @param ResponseInterface|null $resp
	 * @param RequestInterface $req
	 * @return string|null
	 */
	public static function extractErrorMessage( ?ResponseInterface $resp, RequestInterface $req ): ?string {
		if ( !$resp || $resp->getHeader( 'Content-Type' )[0] !== 'text/html' ) {
			return null;
		}

		$message = 'Error performing an external request';
		if ( preg_match( '/^(.*\.)?wikisource.org$/', $req->getUri()->getHost() ) ) {
			$message = 'Wikisource servers returned an error';
		}
		$body = $resp->getBody()->getContents();
		if ( strpos( $body, '<title>Wikimedia Error</title>' ) === false ) {
			return $message;
		}
		$formatter = new HtmlFormatter( $body );
		$doc = $formatter->getDoc();
		$text = null;

		// Try wmerrors style error page
		$xpath = new DOMXPath( $doc );
		$nodes = $xpath->query( '//div[contains(@class, "AdditionalTechnicalStuff")]' );
		/** @var DOMElement $node */
		foreach ( $nodes as $node ) {
			if ( $node->parentNode->getAttribute( 'class' ) === 'TechnicalStuff' ) {
				$text = html_entity_decode( $node->parentNode->textContent );
				break;
			}
		}

		// Otherwise, try hhvm-fatal-error.php style
		if ( !$text ) {
			foreach ( $doc->getElementsByTagName( 'code' ) as $node ) {
				$text = trim( $text . "\n" . $node->textContent );
			}
		}

		return $text ? "$message: $text" : $message;
	}

	/**
	 * Build base DOMDocument from a html string
	 *
	 * @param string $html
	 * @return DOMDocument
	 */
	public static function buildDOMDocumentFromHtml( string $html ): DOMDocument {
		$document = new DOMDocument( '1.0', 'UTF-8' );
		libxml_use_internal_errors( true );
		$document->loadHTML( mb_convert_encoding( str_replace( '<?xml version="1.0" encoding="UTF-8" ?>', '', $html ), 'HTML-ENTITIES', 'UTF-8' ) );
		libxml_clear_errors();
		$document->encoding = 'UTF-8';
		return $document;
	}

	/**
	 * Remove reserved characters from a cache key.
	 * @param string $key
	 * @return string
	 */
	public static function sanitizeCacheKey( string $key ): string {
		return preg_replace( '/[{}()\/\@\:"]/', '-', $key );
	}
}
