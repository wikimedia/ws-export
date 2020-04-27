<?php

namespace App\Generator;

use App\Book;
use App\FileCache;
use App\Util\Util;
use InvalidArgumentException;
use Symfony\Component\Process\Process;

/**
 * @author Thomas Pellissier Tanon
 * @copyright 2015 Thomas Pellissier Tanon
 * @license GPL-2.0-or-later
 */

/**
 * create a file using convert command of Calibre
 */
class ConvertGenerator implements FormatGenerator {

	private static $CONFIG = [
		'htmlz' => [
			'extension' => 'htmlz',
			'mime' => 'application/zip',
			'parameters' => '--page-breaks-before /'
		],
		'epub-2' => [
			'extension' => 'epub',
			'mime' => 'application/epub+zip',
			'parameters' => '--epub-version 2'
		],
		'mobi' => [
			'extension' => 'mobi',
			'mime' => 'application/x-mobipocket-ebook',
			'parameters' => '--page-breaks-before /'
		],
		'pdf-a4' => [
			'extension' => 'pdf',
			'mime' => 'application/pdf',
			'parameters' => '--page-breaks-before / --paper-size a4 --pdf-page-margin-bottom 48 --pdf-page-margin-top 60 --pdf-page-margin-left 36 --pdf-page-margin-right 36 --pdf-page-numbers --preserve-cover-aspect-ratio'
		],
		'pdf-a5' => [
			'extension' => 'pdf',
			'mime' => 'application/pdf',
			'parameters' => '--page-breaks-before / --paper-size a5 --pdf-page-margin-bottom 32 --pdf-page-margin-top 40 --pdf-page-margin-left 24 --pdf-page-margin-right 24 --pdf-page-numbers --preserve-cover-aspect-ratio'
		],
		'pdf-a6' => [
			'extension' => 'pdf',
			'mime' => 'application/pdf',
			'parameters' => '--page-breaks-before / --paper-size a6 --pdf-page-margin-bottom 16 --pdf-page-margin-top 20 --pdf-page-margin-left 12 --pdf-page-margin-right 12 --pdf-page-numbers --preserve-cover-aspect-ratio'
		],
		'pdf-letter' => [
			'extension' => 'pdf',
			'mime' => 'application/pdf',
			'parameters' => '--page-breaks-before / --paper-size letter --pdf-page-margin-bottom 48 --pdf-page-margin-top 60 --pdf-page-margin-left 36 --pdf-page-margin-right 36 --pdf-page-numbers --preserve-cover-aspect-ratio'
		],
		'rtf' => [
			'extension' => 'rtf',
			'mime' => 'application/rtf',
			'parameters' => '--page-breaks-before /'
		],
		'txt' => [
			'extension' => 'txt',
			'mime' => 'text/plain',
			'parameters' => '--page-breaks-before /'
		]
	];

	/**
	 * @return string[]
	 */
	public static function getSupportedTypes() {
		return array_keys( self::$CONFIG );
	}

	/**
	 * @var string
	 */
	private $format;

	/**
	 * @param string $format
	 */
	public function __construct( $format ) {
		if ( !array_key_exists( $format, self::$CONFIG ) ) {
			throw new InvalidArgumentException( 'Invalid format: ' . $format );
		}
		$this->format = $format;
	}

	/**
	 * return the extension of the generated file
	 * @return string
	 */
	public function getExtension() {
		return self::$CONFIG[$this->format]['extension'];
	}

	/**
	 * return the mimetype of the generated file
	 * @return string
	 */
	public function getMimeType() {
		return self::$CONFIG[$this->format]['mime'];
	}

	/**
	 * create the file
	 * @param $data Book the title of the main page of the book in Wikisource
	 * @return string
	 */
	public function create( Book $book ) {
		$outputFileName = Util::buildTemporaryFileName( $book->title, $this->getExtension() );

		try {
			$epubFileName = $this->createEpub( $book );
			$persistentEpubFileName = Util::buildTemporaryFileName( $book->title, 'epub' );
			rename( $epubFileName, $persistentEpubFileName );
			$this->convert( $persistentEpubFileName, $outputFileName );
		} finally {
			if ( isset( $persistentEpubFileName ) ) {
				Util::removeFile( $persistentEpubFileName );
			}
		}

		return $outputFileName;
	}

	private function createEpub( Book $book ) {
		$epubGenerator = new EpubGenerator();
		return $epubGenerator->create( $book );
	}

	private function convert( $epubFileName, $outputFileName ) {
		global $wsexportConfig;

		$command = array_merge(
			[ $this->getEbookConvertCommand(), $epubFileName, $outputFileName ],
			explode( ' ', self::$CONFIG[$this->format]['parameters'] )
		);
		$process = new Process( $command );
		$cache = FileCache::singleton();
		// Calibre environment variables are documented at https://manual.calibre-ebook.com/customize.html
		$process->setEnv( [
			// Sets the directory where configuration files are stored/read.
			'CALIBRE_CONFIG_DIRECTORY' => $cache->getSubdirectory( 'calibre-config' ),
			// Sets the temporary directory used by Calibre.
			'CALIBRE_TEMP_DIR' => $cache->getSubdirectory( 'calibre-temp' ),
			// Sets the directory Calibre uses to cache persistent data between sessions
			'CALIBRE_CACHE_DIRECTORY' => $cache->getSubdirectory( 'calibre-cache' ),
		] );
		$process->setTimeout( $wsexportConfig['exec-timeout'] ?? 120 );
		$process->mustRun();
	}

	private function getEbookConvertCommand() {
		global $wsexportConfig;
		return array_key_exists( 'ebook-convert', $wsexportConfig ) ? $wsexportConfig['ebook-convert'] : 'ebook-convert';
	}
}
