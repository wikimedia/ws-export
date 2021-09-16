<?php

namespace App\Cleaner;

use App\Book;
use App\PageParser;
use App\Util\Util;
use DOMDocument;
use DOMElement;
use DOMXPath;

/**
 * Clean and modify book content in order to epub generation
 */
class BookCleanerEpub {
	private $book;
	private $linksList = [];
	private $baseUrl;

	/**
	 * @param Book $book
	 * @param string $baseUrl base URL of the wiki like http://fr.wikisource.org
	 */
	public function clean( Book $book, $baseUrl ) {
		$this->book = $book;
		$this->baseUrl = $baseUrl;

		$this->encodeTitles();

		if ( $book->content ) {
			$xPath = $this->getXPath( $book->content );
			$this->setHtmlTitle( $xPath, $book->name );
			$this->cleanHtml( $xPath );
		}
		foreach ( $this->book->chapters as $chapter ) {
			$xPath = $this->getXPath( $chapter->content );
			$this->setHtmlTitle( $xPath, $chapter->name );
			$this->cleanHtml( $xPath );
			foreach ( $chapter->chapters as $subpage ) {
				$xPath = $this->getXPath( $subpage->content );
				$this->setHtmlTitle( $xPath, $subpage->name );
				$this->cleanHtml( $xPath );
			}
		}
	}

	protected function encodeTitles() {
		$this->book->title = Util::encodeString( $this->book->title );
		$this->linksList[] = $this->book->title . '.xhtml';
		foreach ( $this->book->chapters as $chapter ) {
			$chapter->title = Util::encodeString( $chapter->title );
			$this->linksList[] = $chapter->title . '.xhtml';
			foreach ( $chapter->chapters as $subpage ) {
				$subpage->title = Util::encodeString( $subpage->title );
				$this->linksList[] = $subpage->title . '.xhtml';
			}
		}
		foreach ( $this->book->pictures as $picture ) {
			$picture->title = Util::encodeString( $picture->title );
			$this->linksList[] = $picture->title;
		}
	}

	protected function getXPath( $file ) {
		$xPath = new DOMXPath( $file );
		$xPath->registerNamespace( 'html', 'http://www.w3.org/1999/xhtml' );

		return $xPath;
	}

	protected function getEmptyDom() {
		$dom = new DOMDocument( '1.0', 'UTF-8' );
		$dom->loadXML( Util::getXhtmlFromContent( $this->book->lang, '' ) );

		return $dom;
	}

	/**
	 * modified the XHTML
	 */
	protected function cleanHtml( DOMXPath $xPath ) {
		$this->setPictureLinks( $xPath );
		$dom = $xPath->document;
		$this->setLinks( $dom );
		$this->addEpubTypeTags( $xPath );
	}

	/**
	 * change the picture links
	 */
	protected function setHtmlTitle( DOMXPath $xPath, $name ) {
		foreach ( $xPath->document->getElementsByTagName( 'title' ) as $titleNode ) {
			$titleNode->nodeValue = $name;
		}
	}

	/**
	 * change the picture links
	 */
	protected function setPictureLinks( DOMXPath $xPath ) {
		$list = $xPath->query( '//img' );
		/** @var DOMElement $node */
		foreach ( $list as $node ) {
			$title = Util::encodeString( $node->getAttribute( 'data-title' ) );
			if ( in_array( $title, $this->linksList ) ) {
				$node->setAttribute( 'src', 'images/' . $title );
			} else {
				$node->parentNode->removeChild( $node );
			}
		}
	}

	/**
	 * change the internal links
	 */
	protected function setLinks( DOMDocument $dom ) {
		$list = $dom->getElementsByTagName( 'a' );
		/** @var DOMElement $node */
		foreach ( $list as $node ) {
			$href = $node->getAttribute( 'href' );
			$title = Util::encodeString( $node->getAttribute( 'title' ) ) . '.xhtml';
			if ( substr( $href, 0, 1 ) === '#' ) {
				continue;
			} elseif ( in_array( $title, $this->linksList ) ) {
				$pos = strpos( $href, '#' );
				if ( $pos !== false ) {
					$anchor = substr( $href, $pos + 1 );
					$title .= '#' . array_search( $anchor, PageParser::getIds() );
				}
				$node->setAttribute( 'href', $title );
			} elseif ( substr( $href, 0, 2 ) === '//' ) {
				$node->setAttribute( 'href', 'http:' . $href );
			} elseif ( substr( $href, 0, 1 ) === '/' ) {
				$node->setAttribute( 'href', $this->baseUrl . $href );
			} elseif ( substr( $href, 0, 2 ) === './' ) {
				$node->setAttribute( 'href', $this->baseUrl . '/wiki/' . substr( $href, 2 ) );
			}
		}
	}

	protected function addEpubTypeTags( DOMXPath $xPath ) {
		$this->addTypeWithXPath( $xPath, '//*[contains(@class, "reference")]/a', 'noteref' );
		$this->addTypeWithXPath( $xPath, '//*[contains(@class, "references")]/li', 'footnote' );
	}

	protected function addTypeWithXPath( DOMXPath $xPath, $query, $type ) {
		$nodes = $xPath->query( $query );
		/** @var DOMElement $node */
		foreach ( $nodes as $node ) {
			$node->setAttributeNS( 'http://www.idpf.org/2007/ops', 'epub:type', $type );
		}
	}
}
