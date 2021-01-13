<?php

namespace App;

use App\Util\Util;
use DOMDocument;
use DOMElement;
use DOMXPath;

class PageParser {

	/** @var DOMXPath */
	protected $xPath;

	/** @var string[] Element ID values, used to fix duplicates. */
	private static $ids = [];

	/**
	 * @param DOMDocument $doc The page to parse
	 */
	public function __construct( DOMDocument $doc ) {
		$this->xPath = new DOMXPath( $doc );
		$this->xPath->registerNamespace( 'html', 'http://www.w3.org/1999/xhtml' );
		$this->removeEnlargeLinks(); // Should be run before getChapterList in order to remove false links
	}

	/**
	 * @return string[]
	 */
	public static function getIds(): array {
		return self::$ids;
	}

	/**
	 * return a metadata in the page
	 * @param string $id the metadata id like ws-author
	 * @return string
	 */
	public function getMetadata( $id ) {
		$node = $this->xPath->query( '//*[@id="' . $id . '" or contains(@class, "' . $id . '")]' );
		if ( $node->length != 0 ) {
			return $node->item( 0 )->nodeValue;
		} else {
			return '';
		}
	}

	/**
	 * return if a metadata exist in the page
	 * @param string $id the metadata id like ws-author
	 * @return bool
	 */
	public function metadataIsSet( $id ) {
		$node = $this->xPath->query( '//*[@id="' . $id . '" or contains(@class, "' . $id . '")]' );

		return $node->length != 0;
	}

	/**
	 * Return the list of the chapters (based on the ws-summary HTML if it exists, otherwise via all internal links).
	 * @param string[] $pageList Array of page titles.
	 * @param string[] $namespaces Array of all localized namespace names of the current wiki.
	 * @return Page[]
	 */
	public function getChaptersList( $pageList, $namespaces ) {
		$list = $this->xPath->query( '//*[
			@id="ws-summary" or
			contains(@class,"ws-summary")]/descendant::a[
				not(
					contains(@class,"new") or
					contains(@href,"action=edit") or
					contains(@class,"extiw") or
					contains(@class,"external") or
					contains(@class,"internal") or
					@rel = "mw:WikiLink/Interwiki" or
					contains(@class,"image")
				)
			]' );
		$chapters = [];
		/** @var DOMElement $link */
		foreach ( $list as $link ) {
			// Extract the page title (including namespace) from the relative URL in the link (via a dummy URL).
			$urlParts = parse_url( 'http://example.com' . $link->getAttribute( 'href' ) );
			if ( empty( $urlParts['path'] ) ) {
				// If there's no path component, it can't be a link to a chapter.
				continue;
			}

			// Remove string "/wiki/" if it's found in $urlParts['path']
			if ( substr( $urlParts['path'], 0, strlen( '/wiki/' ) ) === "/wiki/" ) {
				$title = urldecode( substr( $urlParts['path'], strlen( '/wiki/' ) ) );
			} else {
				$title = urldecode( substr( $urlParts['path'], strlen( '/' ) ) );
			}

			$parts = explode( ':', $title );
			// Include the chapter if it's not already present and is a main-namespace page.
			if ( $title != '' && !in_array( $title, $pageList ) && !in_array( $parts[0], $namespaces ) ) {
				$chapter = new Page();
				$chapter->title = $title;
				$chapter->name = $link->nodeValue;
				$chapters[] = $chapter;
				$pageList[] = $chapter->title;
			}
		}

		return $chapters;
	}

	/**
	 * return the list of the chapters with the summary if it exist, if not find links to subpages.
	 * @return Page[]
	 */
	public function getFullChaptersList( $title, $pageList, $namespaces ) {
		$chapters = $this->getChaptersList( $pageList, $namespaces );
		if ( empty( $chapters ) ) {
			$list = $this->xPath->query( '//a[contains(@href,"' . Util::wfUrlencode( $title ) . '") and
				not(
					contains(@class,"new") or
					contains(@class,"extiw") or
					@rel = "mw:WikiLink/Interwiki" or
					contains(@class,"external") or
					contains(@href,"#") or
					contains(@class,"internal") or
					contains(@href,"action=edit") or
					contains(@title,"/Texte entier") or
					contains(@class,"image")
				)]' );
			/** @var DOMElement $link */
			foreach ( $list as $link ) {
				$linkTitle = str_replace( ' ', '_', $link->getAttribute( 'title' ) );
				$parts = explode( ':', $linkTitle );
				if ( $linkTitle != '' && !in_array( $linkTitle, $pageList ) && !in_array( $parts[0], $namespaces ) ) {
					$chapter = new Page();
					$chapter->title = $linkTitle;
					$chapter->name = $link->nodeValue;
					$chapters[] = $chapter;
					$pageList[] = $chapter->title;
				}
			}
		}

		return $chapters;
	}

	/**
	 * return the pictures of the file, for all handled <img, the alt
	 * attribute is set to the title of the image so the backend can
	 * use it to retrieve the src name without relying on the src= attrib.
	 * @return array
	 */
	public function getPicturesList() {
		$pictures = [];

		$list = $this->xPath->query( '//a[not(contains(@class,"image"))]/img | //img[not(parent::a)]' );
		/** @var DOMElement $img */
		foreach ( $list as $img ) {
			$picture = new Picture();
			$url = $img->getAttribute( 'src' );
			$segments = explode( '/', $url );
			$picture->title = urldecode( $segments[count( $segments ) - 1] );
			$picture->url = $this->resolveProtocolRelativeUrl( $url );
			if ( strpos( $url, '/svg/' ) !== false ) {
				$picture->title .= '.svg';
			}
			$pictures[$picture->title] = $picture;
			$img->setAttribute( 'data-title', $picture->title );

		}

		$list = $this->xPath->query( '
			//a[contains(@class,"image")] |
			//figure[contains(@typeof,"mw:Image")] |
			//figure-inline[contains(@typeof,"mw:Image")]'
		);
		/** @var DOMElement $node */
		foreach ( $list as $node ) {
			/** @var DOMElement $img */
			$img = $node->getElementsByTagName( 'img' )->item( 0 );
			$picture = new Picture();
			$url = $img->getAttribute( 'src' );
			$segments = explode( '/', $url );

			// We need 1st) an unique key for each different image
			// to index the $pictures array. 2nd) the File: name to
			// get the credits for the image, this name can't be
			// used as key because it's not unique, two thumb with
			// different size of the same image will get the same
			// File: name. The url ends with
			// es/thumb/6/62/PD-icon.svg/50px-PD-icon.svg.png or
			// es/2/20/Separador.jpg The url can be used as unique
			// key, so we need only to extract the File:name. This
			// is kludgy as we need to rely on the path format,
			// either the 6/62 part is at pos -4/-3 or -3/-2.
			if ( count( $segments ) >= 4 && is_numeric( "0x" . $segments[count( $segments ) - 4] ) && is_numeric( "0x" . $segments[count( $segments ) - 3] ) ) {
				$picture->name = $segments[count( $segments ) - 2];
				$picture->title = $segments[count( $segments ) - 2] . '-' . $segments[count( $segments ) - 1];
			} else {
				$picture->name = $segments[count( $segments ) - 1];
				$picture->title = $segments[count( $segments ) - 1];
			}
			$picture->url = $this->resolveProtocolRelativeUrl( $url );

			$pictures[$picture->title] = $picture;
			$img->setAttribute( 'data-title', $picture->title );
			$node->parentNode->replaceChild( $img, $node );
		}

		return $pictures;
	}

	private function resolveProtocolRelativeUrl( $url ) {
		if ( strpos( $url, '//' ) === 0 ) {
			return 'https:' . $url;
		} else {
			return $url;
		}
	}

	/**
	 * return the list of the pages of the page namespace included
	 * @return string[]
	 */
	public function getPagesList() {
		$pages = [];
		$list = $this->xPath->query( '//*[contains(@class,"ws-pagenum")]' );
		/** @var DOMElement $link */
		foreach ( $list as $link ) {
			$title = str_replace( ' ', '_', urldecode( $link->getAttribute( 'title' ) ) );
			if ( $title ) {
				$pages[] = $title;
			}
		}

		return $pages;
	}

	/**
	 * return the content cleaned : This action must be done after getting metadata that can be in deleted nodes
	 * @return DOMDocument The page
	 */
	public function getContent( $isMainPage ) {
		$this->removeNodesWithXpath( '//*[contains(@class,"ws-noexport")]' );
		if ( !$isMainPage ) {
			$this->removeNodesWithXpath( '//*[contains(@class,"ws-if-subpage-noexport")]' );
		}
		$this->removeNodesWithXpath( '//*[contains(@class,"mwe-math-mathml-inline")]' ); // TODO: add better MathML support
		$this->removeNodesWithXpath( '//*[@id="toc"]' );
		$this->removeNodesWithXpath( '//span[@class="editsection" or @class="mw-editsection"]' );
		$this->removeNodesWithXpath( '//a[@class="mw-headline-anchor"]' );
		$this->removeNodesWithXpath( '//div[@class="mediaContainer"]' );
		$this->removeNodesWithXpath( '//link[@rel="mw:PageProp/Category"]' );
		$this->deprecatedNodes( 'big', 'span', 'font-size:large;' );
		$this->deprecatedNodes( 'center', 'div', 'text-align:center;' );
		$this->deprecatedNodes( 'strike', 'span', 'text-decoration:line-through;' );
		$this->deprecatedNodes( 's', 'span', 'text-decoration:line-through;' );
		$this->deprecatedNodes( 'u', 'span', 'text-decoration:underline;' );
		$this->deprecatedNodes( 'font', 'span', '' );

		$this->convertAlignAttributes();
		$this->deprecatedAttributes( 'background', 'background-color' );
		$this->deprecatedAttributes( 'bgcolor', 'background-color' );
		$this->deprecatedAttributes( 'border', 'border-width' );
		$this->deprecatedAttributes( 'clear', 'clear' );
		$this->deprecatedAttributes( 'height', 'height' );
		$this->deprecatedAttributes( 'hspace', 'padding' );
		$this->deprecatedAttributes( 'text', 'color' );
		$this->deprecatedAttributes( 'width', 'width' );
		$this->deprecatedAttributes( 'srcset', null );
		$this->deprecatedAttributes( 'cellpadding', 'padding' );
		$this->deprecatedAttributes( 'cellspacing', 'border-spacing' );
		$this->deprecatedAttributes( 'data-file-height', null );
		$this->deprecatedAttributes( 'data-file-width', null );
		$this->deprecatedAttributes( 'data-mw', null );
		$this->deprecatedAttributes( 'lang', 'xml:lang', false );

		$this->cleanRedLinks();
		$this->cleanReferenceLinks();
		$this->cleanIds();
		$this->moveStyleToHead();

		return $this->xPath->document;
	}

	/**
	 * IDs must start with a letter ([A-Za-z]) and may be followed by any number of letters, digits ([0-9]),
	 * hyphens ("-"), underscores ("_"), colons (":"), and periods (".").
	 * This is to ensure maximum compatibility with a wide range of devices.
	 */
	protected function cleanIds(): void {
		// Get all nodes with IDs.
		$list = $this->xPath->query( '//*[@id]' );
		/** @var DOMElement $node */
		foreach ( $list as $node ) {
			$oldId = $node->getAttribute( 'id' );
			$id = $oldId;
			// Check for duplicates and fix them by appending a count.
			if ( array_search( $id, self::$ids ) !== false ) {
				$id .= '-n' . ( array_count_values( self::$ids )[ $id ] + 1 );
			}
			// Must start with a letter.
			if ( preg_match( '/^[A-Za-z].*/', $id ) === 0 ) {
				$id = "id-$id";
			}
			// Can only contain certain characters.
			$id = preg_replace( '/[^A-Za-z0-9\-_:.]/', '_', $id );
			// Set the value.
			if ( $id !== $oldId ) {
				$node->setAttribute( 'id', $id );
				// Also find anything (in the current doc only) that points to the old ID, and update it.
				$hrefList = $this->xPath->query( '//*[contains(@href, concat("#", "' . $oldId . '"))]' );
				/** @var DOMElement $node */
				foreach ( $hrefList as $hrefNode ) {
					$newHref = str_replace( "#$oldId", "#$id", $hrefNode->getAttribute( 'href' ) );
					$hrefNode->setAttribute( 'href', $newHref );
				}
			}
			self::$ids[$id] = $oldId;
		}
	}

	/**
	 * remove links to enlarge pictures
	 */
	protected function removeEnlargeLinks() {
		$this->removeNodesWithXpath( '//*[contains(@class,"magnify")]' );
	}

	protected function removeNodesWithXpath( $query ) {
		$nodes = $this->xPath->query( $query );
		foreach ( $nodes as $node ) {
			$node->parentNode->removeChild( $node );
		}
	}

	protected function deprecatedNodes( $oldName, $newName, $style ) {
		$nodes = $this->xPath->query( '//' . $oldName ); // hack: the getElementsByTagName method doesn't catch all tags.
		foreach ( $nodes as $oldNode ) {
			$newNode = $this->xPath->document->createElement( $newName );
			while ( $oldNode->firstChild ) {
				$newNode->appendChild( $oldNode->firstChild );
			}
			foreach ( $oldNode->attributes as $attribute ) {
				$newNode->setAttribute( $attribute->name, $attribute->value );
			}
			$newNode->setAttribute( 'style', $style . ' ' . $newNode->getAttribute( 'style' ) );
			$oldNode->parentNode->replaceChild( $newNode, $oldNode );
		}
	}

	protected function deprecatedAttributes( $name, $attribute, $isCss = true ) {
		$nodes = $this->xPath->query( '//*[@' . $name . ']' );
		/** @var DOMElement $node */
		foreach ( $nodes as $node ) {
			if ( $attribute != null ) {
				if ( $isCss ) {
					$node->setAttribute( 'style', $attribute . ':' . $node->getAttribute( $name ) . '; ' . $node->getAttribute( 'style' ) );
				} else {
					$node->setAttribute( $attribute, $node->getAttribute( $name ) );
				}
			}
			$node->removeAttribute( $name );
		}
	}

	private function convertAlignAttributes() {
		$nodes = $this->xPath->query( '//*[@align]' );
		/** @var DOMElement $node */
		foreach ( $nodes as $node ) {
			$alignment = $node->getAttribute( 'align' );
			// Tables have their own (deprecated) align attribute.
			// https://developer.mozilla.org/en-US/docs/Web/API/HTMLTableElement/align
			if ( $node->tagName === 'table' && $alignment === 'left' ) {
				$css = "margin-right: auto";
			} elseif ( $node->tagName === 'table' && $alignment === 'right' ) {
				$css = "margin-left: auto";
			} elseif ( $node->tagName === 'table' && $alignment === 'center' ) {
				$css = "margin: auto";
			} else {
				$css = 'text-align: ' . $alignment;
			}
			$node->setAttribute( 'style', trim( $css . '; ' . $node->getAttribute( 'style' ) ) );
			$node->removeAttribute( 'align' );
		}
	}

	private function cleanRedLinks() {
		$list = $this->xPath->query( '//a[contains(@href,"action=edit")]' );
		/** @var DOMElement $node */
		foreach ( $list as $node ) {
			foreach ( $node->childNodes as $childNode ) {
				if ( $childNode !== $node ) {
					$node->parentNode->insertBefore( $childNode, $node );
				}
			}
			$node->parentNode->removeChild( $node );
		}
	}

	private function cleanReferenceLinks() {
		// Get all links that contain the "style" value "mw-Ref"
		$links = $this->xPath->query( '//html:a[contains(@style, "mw-Ref")]' );
		foreach ( $links as $link ) {
			$href = $link->getAttribute( 'href' );
			$pos = strpos( $href, '#' );
			$link->setAttribute( 'href', substr( $href, $pos ) );
		}
		// Get all links that have the "rel" attribute equals to "mw-Ref"
		$links = $this->xPath->query( '//html:a[@rel="mw:referencedBy"]' );
		foreach ( $links as $link ) {
			$href = $link->getAttribute( 'href' );
			$pos = strpos( $href, '#' );
			$link->setAttribute( 'href', substr( $href, $pos ) );
		}
	}

	private function moveStyleToHead() {
		/** @var DOMElement $head */
		foreach ( $this->xPath->query( '//head' ) as $head ) {
			/** @var DOMElement $style */
			foreach ( $this->xPath->query( '//body//style' ) as $style ) {
				$style->parentNode->removeChild( $style );
				$head->appendChild( $style );
				// Also remove unsed data-mw-deduplicate attribute (see below for why).
				$style->removeAttribute( 'data-mw-deduplicate' );
			}
		}

		// Remove TemplateStyles link elements from the body. T244448.
		// These are now not required as the styles are present in the head of the chapter.
		// For example: <link rel="mw-deduplicated-inline-style" href="mw-data:TemplateStyles:r9031029"/>
		/** @var DOMElement $link */
		foreach ( $this->xPath->query( '//link[@rel="mw-deduplicated-inline-style"]' ) as $link ) {
			$link->parentNode->removeChild( $link );
		}
	}
}
