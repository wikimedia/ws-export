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
	 * @var int
	 */
	private $randomNumber;

	/**
	 * @param string $format
	 */
	public function __construct( $format ) {
		if( !array_key_exists( $format, self::$CONFIG ) ) {
			throw new InvalidArgumentException( 'Invalid format: ' . $format );
		}
		$this->format = $format;
		$this->randomNumber = rand();
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
		$this->createEpub( $book );
		$this->convert( $book->title );
		return $this->getFileContent( $book->title );
	}

	private function createEpub( Book $book ) {
		$epubGenerator = new Epub3Generator();
		file_put_contents( $this->buildFileName( $book->title, 'epub' ), $epubGenerator->create( $book ) );
	}

	private function convert( $title ) {
		$output = array();
		$returnStatus = 0;

		exec(
			$this->getEbookConvertCommand() . ' ' .
			$this->buildFileName( $title, 'epub' ) . ' ' .
			$this->buildFileName( $title, $this->getExtension() ) . ' ' .
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
		global $wsexportConfig;
		return $wsexportConfig['tempPath'] . '/' . encodeString( $bookTitle ) . '-' . $this->randomNumber . '.' . $extension;
	}

	private function getFileContent( $title ) {
		$content = file_get_contents( $this->buildFileName( $title, $this->getExtension() ) );
		unlink( $this->buildFileName( $title, 'epub' ) );
		unlink( $this->buildFileName( $title, $this->getExtension() ) );
		return $content;
	}
}
