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

	private static $CONFIG = array(
		'mobi' => array(
			'extension' => 'mobi',
			'mime' => 'application/x-mobipocket-ebook',
			'parameters' => ''
		),
		'pdf-a4' => array(
			'extension' => 'pdf',
			'mime' => 'application/pdf',
			'parameters' => '--paper-size a4 --margin-bottom 48 --margin-top 60 --margin-left 36 --margin-right 36 --pdf-page-numbers --preserve-cover-aspect-ratio'
		),
		'pdf-a5' => array(
			'extension' => 'pdf',
			'mime' => 'application/pdf',
			'parameters' => '--paper-size a5 --margin-bottom 32 --margin-top 40 --margin-left 24 --margin-right 24 --pdf-page-numbers --preserve-cover-aspect-ratio'
		),
		'pdf-letter' => array(
			'extension' => 'pdf',
			'mime' => 'application/pdf',
			'parameters' => '--paper-size letter --margin-bottom 48 --margin-top 60 --margin-left 36 --margin-right 36 --pdf-page-numbers --preserve-cover-aspect-ratio'
		),
		'rtf' => array(
			'extension' => 'rtf',
			'mime' => 'application/rtf',
			'parameters' => ''
		),
		'txt' => array(
			'extension' => 'txt',
			'mime' => 'text/plain',
			'parameters' => ''
		)
	);

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
		if( !array_key_exists( $format, self::$CONFIG ) ) {
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
		$epubFileName = $this->buildFileName( $book->title, 'epub' );
		$outputFileName = $this->buildFileName( $book->title, $this->getExtension() );

		$this->createEpub( $book, $epubFileName );
		$this->convert( $epubFileName, $outputFileName );

		$content = file_get_contents( $outputFileName );
		unlink( $epubFileName );
		unlink( $outputFileName );
		return $content;
	}

	private function createEpub( Book $book, $epubFileName ) {
		$epubGenerator = new Epub3Generator();
		file_put_contents( $epubFileName, $epubGenerator->create( $book ) );
	}

	private function convert( $epubFileName, $outputFileName ) {
		$output = array();
		$returnStatus = 0;

		exec(
			$this->getEbookConvertCommand() . ' ' .
			$epubFileName . ' ' .
			$outputFileName . ' ' .
			self::$CONFIG[$this->format]['parameters'],
			$output,
			$returnStatus
		);

		if( $returnStatus !== 0 ) {
			throw new Exception( 'Conversion to ' . $this->getExtension() . ' failed.' );
		}
	}

	private function getEbookConvertCommand() {
		global $wsexportConfig;
		return array_key_exists( 'ebook-convert', $wsexportConfig ) ? $wsexportConfig['ebook-convert'] : 'ebook-convert';
	}

	private function buildFileName( $bookTitle, $extension ) {
		return tempnam( sys_get_temp_dir(), encodeString( $bookTitle ) ) . '.' . $extension;
	}
}
