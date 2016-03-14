<?php
/**
 * @author Thomas Pellissier Tanon
 * @copyright 2015 Thomas Pellissier Tanon
 * @licence http://www.gnu.org/licenses/gpl.html GNU General Public Licence
 */

/**
 * create an an xhtml file
 * @see http://www.w3.org/TR/html5/
 */
class AtomGenerator implements FormatGenerator {

	/**
	 * @var string
	 */
	private $exportBasePath;

	/**
	 * @param string $exportBasePath
	 */
	public function __construct( $exportBasePath = '' ) {
		$this->exportBasePath = $exportBasePath;
	}

	/**
	 * return the extension of the generated file
	 * @return string
	 */
	public function getExtension() {
		return 'atom';
	}

	/**
	 * return the mimetype of the generated file
	 * @return string
	 */
	public function getMimeType() {
		return 'application/atom+xml;type=entry;profile=opds-catalog;charset=utf-8';
	}

	/**
	 * create the file
	 * @var $data Book the title of the main page of the book in Wikisource
	 * @return string
	 */
	public function create( Book $book ) {
		$dom = new DOMDocument( "1.0", "UTF-8" );
		$entry = $this->buildEntry( $book, $dom );
		$this->appendNamespaces( $entry );
		$dom->appendChild( $entry );

		$fileName = buildTemporaryFileName( $book->title, 'atom' );
		$dom->save( $fileName );
		return $fileName;
	}

	public function appendNamespaces( DOMElement $node ) {
		$node->setAttribute( 'xmlns', 'http://www.w3.org/2005/Atom' );
		$node->setAttribute( 'xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance' );
		$node->setAttribute( 'xmlns:dc', 'http://purl.org/dc/elements/1.1/' );
		$node->setAttribute( 'xmlns:dcterms', 'http://purl.org/dc/terms/' );
	}

	public function buildEntry( Book $book, DOMDocument $dom ) {
		date_default_timezone_set( 'UTC' );
		$wsUrl = wikisourceUrl( $book->lang, $book->title );

		$node = $dom->createElement( 'entry' );
		$node->setAttribute( 'xml:lang', $book->lang );

		$this->addNode( $dom, $node, 'title', $book->name );
		$this->addNode( $dom, $node, 'id', $wsUrl, 'dcterms:URI' );
		// TODO published?
		$this->addNode( $dom, $node, 'updated', date( DATE_ATOM ) );
		$this->addNode( $dom, $node, 'rights', 'http://creativecommons.org/licenses/by-sa/3.0' );
		$this->addPersonNode( $dom, $node, 'author', $book->author );
		$this->addPersonNode( $dom, $node, 'contributor', $book->translator );
		$this->addPersonNode( $dom, $node, 'contributor', $book->illustrator );

		foreach ( $book->categories as $categorie ) {
			$cat = $dom->createElement( 'category' );
			$cat->setAttribute( 'label', $categorie );
			$cat->setAttribute( 'term', $categorie );
			$node->appendChild( $cat );
		}

		$this->addNode( $dom, $node, 'dc:identifier', $wsUrl, 'dcterms:URI' );
		$this->addNode( $dom, $node, 'dc:language', $book->lang, 'dcterms:RFC4646' );
		$this->addNode( $dom, $node, 'dc:source', wikisourceUrl( $book->lang, $book->title ), 'dcterms:URI' );
		$this->addNode( $dom, $node, 'dcterms:issued', $book->year, 'dcterms:W3CDTF' );
		$this->addNode( $dom, $node, 'dc:publisher', $book->publisher );

		$this->addLink( $dom, $node, 'alternate', $this->buildExportUrl( $book, 'atom' ), 'application/atom+xml;type=entry;profile=opds-catalog' );
		$this->addLink( $dom, $node, 'alternate', $wsUrl, 'text/html' );
		$this->addLink( $dom, $node, 'http://opds-spec.org/acquisition', $this->buildExportUrl( $book, 'epub' ), 'application/epub+zip' );
		$this->addLink( $dom, $node, 'http://opds-spec.org/acquisition', $this->buildExportUrl( $book, 'mobi' ), 'application/x-mobipocket-ebook' );
		$this->addLink( $dom, $node, 'http://opds-spec.org/acquisition', $this->buildExportUrl( $book, 'xhtml' ), 'application/xhtml+xml' );
		if ( $book->cover !== '' ) {
			$this->addLink( $dom, $node, 'http://opds-spec.org/image', $book->pictures[$book->cover]->url, $book->pictures[$book->cover]->mimetype );
		}

		return $node;
	}

	private function addNode( DOMDocument $dom, DOMElement $head, $name, $value, $type = '' ) {
		if ( $value === '' ) {
			return;
		}

		$node = $dom->createElement( $name, $value );

		if ( $type !== '' ) {
			$node->setAttribute( 'xsi:type', $type );
		}

		$head->appendChild( $node );
	}

	private function addPersonNode( DOMDocument $dom, DOMElement $head, $type, $name = '' ) {
		if ( $name === '' ) {
			return;
		}

		$person = $dom->createElement( $type );
		$this->addNode( $dom, $person, 'name', $name );
		$head->appendChild( $person );
	}

	private function addLink( DOMDocument $dom, DOMElement $head, $rel, $href, $type = '' ) {
		$node = $dom->createElement( 'link' );
		$node->setAttribute( 'rel', $rel );
		if ( $type !== '' ) {
			$node->setAttribute( 'type', $type );
		}
		$node->setAttribute( 'href', $href );
		$head->appendChild( $node );
	}

	private function buildExportUrl( Book $book, $format ) {
		return $this->exportBasePath . '?' . http_build_query( [ 'lang' => $book->lang, 'format' => $format, 'page' => $book->title ] );
	}
}
