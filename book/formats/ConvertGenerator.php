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

	/**
	 * @var string
	 */
	private $extension;

	/**
	 * @var string
	 */
	private $mimeType;

	/**
	 * @param string $extension
	 * @param string $mimeType
	 */
	public function __construct( $extension, $mimeType ) {
		$this->extension = $extension;
		$this->mimeType = $mimeType;
	}

	/**
	 * return the extension of the generated file
	 * @return string
	 */
	public function getExtension() {
		return $this->extension;
	}

	/**
	 * return the mimetype of the generated file
	 * @return string
	 */
	public function getMimeType() {
		return $this->mimeType;
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
		exec(
			$this->getEbookConvertCommand() . ' ' .
			$this->buildFileName( $title, 'epub' ) . ' ' .
			$this->buildFileName( $title, $this->extension )
		);
	}

	private function getEbookConvertCommand() {
		global $wsexportConfig;
		return array_key_exists( 'ebook-convert', $wsexportConfig ) ? $wsexportConfig['ebook-convert'] : 'ebook-convert';
	}

	private function buildFileName( $bookTitle, $extension ) {
		global $wsexportConfig;
		return $wsexportConfig['tempPath'] . '/' . encodeString( $bookTitle ) . '.' . $extension;
	}

	private function getFileContent( $title ) {
		$content = file_get_contents( $this->buildFileName( $title, $this->extension ) );
		unlink( $this->buildFileName( $title, 'epub' ) );
		unlink( $this->buildFileName( $title, $this->extension ) );
		return $content;
	}
}
