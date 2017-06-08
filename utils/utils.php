<?php
/**
 * @author Thomas Pellissier Tanon
 * @copyright 2011 Thomas Pellissier Tanon
 * @licence http://www.gnu.org/licenses/gpl.html GNU General Public Licence
 */

/**
 * @param string $prefix a prefix for the uuid
 * @return string an UUID
 */
function uuid( $prefix = '' ) {
	$chars = md5( uniqid( mt_rand(), true ) );
	$uuid = substr( $chars, 0, 8 ) . '-';
	$uuid .= substr( $chars, 8, 4 ) . '-';
	$uuid .= substr( $chars, 12, 4 ) . '-';
	$uuid .= substr( $chars, 16, 4 ) . '-';
	$uuid .= substr( $chars, 20, 12 );

	return $prefix . $uuid;
}

/**
 * @param string $lang the language of the wiki
 * @param string $page the name of the page
 * @return string an url to a page of Wikisource
 */
function wikisourceUrl( $lang, $page = '' ) {
	if ( $lang === '' ) {
		$url = 'http://wikisource.org';
	} else {
		$url = 'http://' . $lang . '.wikisource.org';
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
function getFile( $file ) {
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
 * Get mimetype of a file, using finfo if its available, or mime_magic.
 *
 * @param string $contents a buffer containing the contents of the file
 * @return string|bool mime type on success or false on failure
 */
function getMimeType( $contents ) {
	if ( class_exists( 'finfo', false ) ) {
		$finfoOpt = defined( 'FILEINFO_MIME_TYPE' ) ? FILEINFO_MIME_TYPE : FILEINFO_MIME;
		$info = new finfo( $finfoOpt );
		if ( $info ) {
			return $info->buffer( $contents );
		}
	}
	if ( ini_get( 'mime_magic.magicfile' ) && function_exists( 'mime_content_type' ) ) {
		$filename = tempnam( sys_get_temp_dir(), 'wsf' );
		file_put_contents( $filename, $contents );
		$ret = mime_content_type( $filename );
		removeFile( $filename );

		return $ret;
	}

	return false;
}

/**
 * get an xhtml page from a text content
 * @param string $lang content language code
 * @param string $content
 * @param string $title
 * @return string
 */
function getXhtmlFromContent( $lang, $content, $title = ' ' ) {
	if ( $content != '' ) {
		$content = preg_replace( '#<\!--(.+)-->#isU', '', $content );
	}
	$html = '<?xml version="1.0" encoding="UTF-8" ?><!DOCTYPE html><html xmlns="http://www.w3.org/1999/xhtml"';
	if ( $lang != null ) {
		$html .= ' xml:lang="' . $lang . '" dir="' . getLanguageDirection( $lang ) . '"';
	}

	return $html . '><head><meta content="application/xhtml+xml;charset=UTF-8" http-equiv="default-style" /><link type="text/css" rel="stylesheet" href="main.css" /><title>' . $title . '</title></head><body>' . $content . '</body></html>';
}

function getTempFile( $lang, $name ) {
	global $wsexportConfig;
	$path = $wsexportConfig['tempPath'] . '/' . $lang . '/' . $name;
	if ( !file_exists( $path ) ) {
		$refresh = new Refresh( new Api( $lang ) );
		$refresh->refresh();
	}
	return file_get_contents( $path );
}

function getI18n( $lang ) {
	return unserialize( getTempFile( $lang, 'i18n.sphp' ) );
}

function encodeString( $string ) {
	static $map = [];
	static $num = 0;
	$string = str_replace( ' ', '_', $string );
	if ( isset( $map[$string] ) ) {
		return $map[$string];
	}
	$map[$string] = $string;
	$search = [ '[αάàâäΑÂÄ]', '[βΒ]', '[Ψç]', '[δΔ]', '[εéèêëΕÊË]', '[η]', '[φϕΦ]', '[γΓ]', '[θΘ]', '[ιîïΙÎÏ]', '[Κκ]', '[λΛ]', '[μ]', '[ν]', '[οôöÔÖ]', '[Ωω]', '[πΠ]', '[Ψψ]', '[ρΡ]', '[σΣ]', '[τ]', '[υûùüΥÛÜ]', '[ξΞ]', '[ζΖ]', '[ ]', '[^a-zA-Z0-9_\.]' ];
	$replace = [ 'a', 'b', 'c', 'd', 'e', 'eh', 'f', 'g', 'h', 'i', 'k', 'l', 'm', 'n', 'o', 'oh', 'p', 'ps', 'r', 's', 't', 'u', 'x', 'z', '_', '_' ];
	mb_regex_encoding( 'UTF-8' );
	foreach ( $search as $i => $pat ) {
		$map[$string] = mb_eregi_replace( $pat, $replace[$i], $map[$string] );
	}
	$map[$string] = 'c' . $num . '_' . cutFilename( utf8_decode( $map[$string] ) );
	$num++;

	return $map[$string];
}

/**
 * Cut a filename if it is too long but keep the extension
 */
function cutFilename( $string, $max = 100 ) {
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
function getLanguageDirection( $languageCode ) {
	return
		in_array( $languageCode, [ 'ar', 'arc', 'bcc', 'bqi', 'ckb', 'dv', 'fa', 'glk', 'he', 'lrc', 'mzn', 'pnb', 'ps', 'sd', 'ug', 'ur', 'yi' ] )
		? 'rtl'
		: 'ltr';
}

/**
 * Builds a unique temporary file name for a given title and extension
 *
 * @param string $title
 * @param string $extension
 * @param bool $systemTemp Use the system /tmp directory
 * @return string
 */
function buildTemporaryFileName( $title, $extension, $systemTemp = false ) {
	if ( $systemTemp ) {
		$directory = sys_get_temp_dir();
	} else {
		global $wsexportConfig;
		$directory = realpath( $wsexportConfig['tempPath'] );
	}

	for ( $i = 0; $i < 100; $i++ ) {
		$path = $directory . '/' . 'ws-' . encodeString( $title ) . '-' . getmypid() . rand() . '.' . $extension;
		if ( !file_exists( $path ) ) {
			return $path;
		}
	}

	throw new Exception( 'Unable to create temporary file' );
}

function removeFile( $fileName ) {
	exec( 'rm ' . escapeshellcmd( realpath( $fileName ) ), $output, $result );
	if ( $result !== 0 ) {
		error_log( 'rm failed on file ' . $fileName . ' with output: ' . implode( '\n', $output ) );
	}
	return $result === 0;
}
