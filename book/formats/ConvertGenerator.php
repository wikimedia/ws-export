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

	public function __construct( $extension, $mimeType ) {

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
		return file_get_contents( $this->buildFileName( $book->title, $this->extension ) );
	}

	private function createEpub( Book $book ) {
		global $wsexportConfig;
		$filePath = $wsexportConfig['tempPath'] . '/' . $book->title . '.epub';

		$epubGenerator = new Epub3Generator();
		file_put_contents( $this->buildFileName( $book->title, 'epub' ), $epubGenerator->create( $book ) );

		return $filePath;
	}

	private function convert( $title ) {
		exec( $this->buildFileName( $title, 'epub' ) . $this->buildFileName( $title, $this->extension ) ); //TODO
	}

	private function buildFileName( $bookTitle, $extension ) {
		global $wsexportConfig;
		return $wsexportConfig['tempPath'] . '/' . $bookTitle . '.' . $extension;
	}
}

