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
		'mobi' => [
			'extension' => 'mobi',
			'mime' => 'application/x-mobipocket-ebook',
			'parameters' => ''
		],
		'pdf-a4' => [
			'extension' => 'pdf',
			'mime' => 'application/pdf',
			'parameters' => '--paper-size a4 --margin-bottom 48 --margin-top 60 --margin-left 36 --margin-right 36 --preserve-cover-aspect-ratio'
		],
		'pdf-a5' => [
			'extension' => 'pdf',
			'mime' => 'application/pdf',
			'parameters' => '--paper-size a5 --margin-bottom 32 --margin-top 40 --margin-left 24 --margin-right 24 --preserve-cover-aspect-ratio'
		],
		'pdf-letter' => [
			'extension' => 'pdf',
			'mime' => 'application/pdf',
			'parameters' => '--paper-size letter --margin-bottom 48 --margin-top 60 --margin-left 36 --margin-right 36 --preserve-cover-aspect-ratio'
		],
		'rtf' => [
			'extension' => 'rtf',
			'mime' => 'application/rtf',
			'parameters' => ''
		],
		'txt' => [
			'extension' => 'txt',
			'mime' => 'text/plain',
			'parameters' => ''
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

		$epubFileName = $this->createEpub( $book );
		$this->convert( $epubFileName, $outputFileName );
		unlink( $epubFileName );

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
