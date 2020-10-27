<?php

namespace App\Cleaner;

use App\Book;
use App\Page;
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
		$this->splitChapters();

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

	protected function splitChapters() {
		$chapters = [];
		if ( $this->book->content ) {
			$main = $this->splitChapter( $this->book );
			$this->book->content = $main[0]->content;
			if ( !empty( $main ) ) {
				unset( $main[0] );
				$chapters = $main;
			}
		}
		foreach ( $this->book->chapters as $chapter ) {
			$chapters = array_merge( $chapters, $this->splitChapter( $chapter ) );
		}
		$this->book->chapters = $chapters;
	}

	/*
	 * Credit for the tricky part of this code: Asbjorn Grandt
	 * https://github.com/Grandt/PHPePub/blob/master/EPubChapterSplitter.php
	 */
	protected function splitChapter( Page $chapter ) {
		$partSize = 250000;
		$length = strlen( $chapter->content->saveXML() );
		if ( $length <= $partSize ) {
			return [ $chapter ];
		}

		$parts = ceil( $length / $partSize );
		$partSize = ( $length / $parts ) + 2000;

		$pages = [];

		$files = [];
		$domDepth = 0;
		$domPath = [];
		$domClonedPath = [];

		$curFile = $chapter->content->createDocumentFragment();
		$files[] = $curFile;
		$curParent = $curFile;
		$curSize = 0;

		$head = $chapter->content->getElementsByTagName( 'head' )->item( 0 );
		$body = $chapter->content->getElementsByTagName( 'body' )->item( 0 );
		$node = $body->firstChild;
		do {
			$nodeData = $chapter->content->saveXML( $node );
			$nodeLen = strlen( $nodeData );

			if ( $nodeLen > $partSize && $node->hasChildNodes() ) {
				$domPath[] = $node;
				$domClonedPath[] = $node->cloneNode( false );
				$domDepth++;

				$node = $node->firstChild;

				$nodeData = $chapter->content->saveXML( $node );
				$nodeLen = strlen( $nodeData );
			}

			$next_node = $node->nextSibling;

			if ( $node != null && $node->nodeName !== "#text" ) {
				if ( $curSize > 0 && $curSize + $nodeLen > $partSize ) {
					$curFile = $chapter->content->createDocumentFragment();
					$files[] = $curFile;
					$curParent = $curFile;
					if ( $domDepth > 0 ) {
						foreach ( $domClonedPath as $v ) {
							$newParent = $v->cloneNode( false );
							$curParent->appendChild( $newParent );
							$curParent = $newParent;
						}
					}
					$curSize = strlen( $chapter->content->saveXML( $curFile ) );
				}
			}

			$curParent->appendChild( $node->cloneNode( true ) );
			$curSize += $nodeLen;

			$node = $next_node;
			while ( $node == null && $domDepth > 0 ) {
				$domDepth--;
				$node = end( $domPath )->nextSibling;
				array_pop( $domPath );
				array_pop( $domClonedPath );
				if ( $curParent->parentNode ) {
					$curParent = $curParent->parentNode;
				}
			}
		} while ( $node != null );

		foreach ( $files as $idx => $file ) {
			$xml = $this->getEmptyDom();
			$newHead = $xml->getElementsByTagName( 'head' )->item( 0 );
			foreach ( $head->childNodes as $childNode ) {
				$newHead->appendChild( $xml->importNode( $childNode, true ) );
			}
			$newBody = $xml->getElementsByTagName( 'body' )->item( 0 );
			$newBody->appendChild( $xml->importNode( $file, true ) );
			foreach ( $body->attributes as $attribute ) {
				$newBody->setAttribute( $attribute->nodeName, $attribute->nodeValue );
			}
			$page = new Page();
			if ( $idx == 0 ) {
				$page->title = $chapter->title;
				$page->name = $chapter->name;
			} else {
				$page->title = $chapter->title . '_' . ( $idx + 1 );
			}
			$page->content = $xml;
			$pages[] = $page;
		}

		return $pages;
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
					if ( is_numeric( $anchor ) ) {
						$title .= '#_' . $anchor;
					} else {
						$title .= '#' . $anchor;
					}
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
