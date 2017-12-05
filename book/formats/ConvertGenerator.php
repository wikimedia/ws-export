<?php
/**
 * @author Thomas Pellissier Tanon
 * @copyright 2015 Thomas Pellissier Tanon
 * @licence http://www.gnu.org/licenses/gpl.html GNU General Public Licence
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
		'mobi' => [
			'extension' => 'mobi',
			'mime' => 'application/x-mobipocket-ebook',
			'parameters' => '--page-breaks-before /'
		],
		'pdf-a4' => [
			'extension' => 'pdf',
			'mime' => 'application/pdf',
			'parameters' => '--page-breaks-before / --paper-size a4 --margin-bottom 48 --margin-top 60 --margin-left 36 --margin-right 36 --pdf-page-numbers --preserve-cover-aspect-ratio'
		],
		'pdf-a5' => [
			'extension' => 'pdf',
			'mime' => 'application/pdf',
			'parameters' => '--page-breaks-before / --paper-size a5 --margin-bottom 32 --margin-top 40 --margin-left 24 --margin-right 24 --pdf-page-numbers --preserve-cover-aspect-ratio'
		],
		'pdf-a6' => [
			'extension' => 'pdf',
			'mime' => 'application/pdf',
			'parameters' => '--page-breaks-before / --paper-size a6 --margin-bottom 16 --margin-top 20 --margin-left 12 --margin-right 12 --pdf-page-numbers --preserve-cover-aspect-ratio'
		],
		'pdf-letter' => [
			'extension' => 'pdf',
			'mime' => 'application/pdf',
			'parameters' => '--page-breaks-before / --paper-size letter --margin-bottom 48 --margin-top 60 --margin-left 36 --margin-right 36 --pdf-page-numbers --preserve-cover-aspect-ratio'
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
	 * @var $data Book the title of the main page of the book in Wikisource
	 * @return string
	 */
	public function create( Book $book ) {
		$outputFileName = buildTemporaryFileName( $book->title, $this->getExtension() );

		try {
			$epubFileName = $this->createEpub( $book );
			$persistentEpubFileName = buildTemporaryFileName( $book->title, 'epub' );
			rename( $epubFileName, $persistentEpubFileName );
			$this->convert( $persistentEpubFileName, $outputFileName );
		} finally {
			if ( isset( $persistentEpubFileName ) ) {
				removeFile( $persistentEpubFileName );
			}
		}

		return $outputFileName;
	}

	private function createEpub( Book $book ) {
		$epubGenerator = new Epub3Generator();
		return $epubGenerator->create( $book );
	}

	private function convert( $epubFileName, $outputFileName ) {
		$output = [];
		$returnStatus = 0;

		exec(
			$this->getEbookConvertCommand() . ' ' .
			escapeshellarg( $epubFileName ) . ' ' .
			escapeshellarg( $outputFileName ) . ' ' .
			self::$CONFIG[$this->format]['parameters'],
			$output,
			$returnStatus
		);

		if ( $returnStatus !== 0 ) {
			throw new Exception( 'Conversion to ' . $this->getExtension() . ' failed.' );
		}
	}

	private function getEbookConvertCommand() {
		global $wsexportConfig;
		return array_key_exists( 'ebook-convert', $wsexportConfig ) ? $wsexportConfig['ebook-convert'] : 'ebook-convert';
	}
}
