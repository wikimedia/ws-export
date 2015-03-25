<?php
/**
 * @author Thomas Pellissier Tanon
 * @copyright 2011 Thomas Pellissier Tanon
 * @licence http://www.gnu.org/licenses/gpl.html GNU General Public Licence
 */

/**
 * create an epub file
 */
abstract class EpubGenerator implements FormatGenerator {

	/**
	 * array key/value that contain translated strings
	 */
	protected $i18n = array();

	/**
	 * return the extension of the generated file
	 * @return string
	 */
	public function getExtension() {
		return 'epub';
	}

	/**
	 * return the mimetype of the generated file
	 * @return string
	 */
	public function getMimeType() {
		return 'application/epub+zip';
	}

	/**
	 * @return integer ePub version
	 */
	protected abstract function getVersion();

	/**
	 * create the file
	 * @var $data Book the content of the book
	 * @return string
	 */
	public function create( Book $book ) {
		$css = $this->getCss( $book );
		$this->i18n = getI18n( $book->lang );
		setLocale( LC_TIME, $book->lang . '_' . strtoupper( $book->lang ) . '.utf8' );
		$wsUrl = wikisourceUrl( $book->lang, $book->title );
		$cleaner = new BookCleanerEpub($this->getVersion());
		$cleaner->clean( $book );
		$zip = new ZipCreator();
		$zip->addContentFile( 'mimetype', 'application/epub+zip', null, false ); //the mimetype must be first and uncompressed
		$zip->addContentFile( 'META-INF/container.xml', $this->getXmlContainer() );
		$zip->addContentFile( 'OPS/content.opf', $this->getOpfContent( $book, $wsUrl ) );
		$zip->addContentFile( 'OPS/toc.ncx', $this->getNcxToc( $book, $wsUrl ) );
		if( $book->cover != '' ) {
			$zip->addContentFile( 'OPS/cover.xhtml', $this->getXhtmlCover( $book ) );
		}
		$zip->addContentFile( 'OPS/title.xhtml', $this->getXhtmlTitle( $book ) );
		$zip->addContentFile( 'OPS/about.xhtml', $this->getXhtmlAbout( $book, $wsUrl ) );
		$dir = dirname( __FILE__ );
		$zip->addFile( $dir . '/images/Accueil_scribe.png', 'OPS/images/Accueil_scribe.png' );

		$font = FontProvider::getData( $book->options['fonts'] );
		if( $font !== null ) {
			foreach( $font['otf'] as $name => $path ) {
				$zip->addFile( $dir . '/fonts/' . $font['name'] . '/' . $path, 'OPS/fonts/' . $font['name'] . $name . '.otf' );
			}
		}

		if( $book->content ) {
			$zip->addContentFile( 'OPS/' . $book->title . '.xhtml', $book->content->saveXML() );
		}
		if( !empty( $book->chapters ) ) {
			foreach( $book->chapters as $chapter ) {
				$zip->addContentFile( 'OPS/' . $chapter->title . '.xhtml', $chapter->content->saveXML() );
				foreach( $chapter->chapters as $subpage ) {
					$zip->addContentFile( 'OPS/' . $subpage->title . '.xhtml', $subpage->content->saveXML() );
				}
			}
		}
		foreach( $book->pictures as $picture ) {
			$zip->addContentFile( 'OPS/images/' . $picture->title, $picture->content );
		}
		$zip->addContentFile( 'OPS/main.css', $css );
		$this->addContent( $book, $zip );

		return $zip->getContent();
	}

	/**
	 * add extra content to the file
	 * @var $book Book
	 * @var $zip ZipCreator
	 */
	protected abstract function addContent( Book $book, ZipCreator $zip );

	/**
	 * return the OPF descrition file
	 * @var $book Book
	 * @var $wsUrl URL to the main page in Wikisource
	 */
	protected abstract function getOpfContent( Book $book, $wsUrl );

	protected function getXmlContainer() {
		$content = '<?xml version="1.0" encoding="UTF-8" ?>
			<container version="1.0" xmlns="urn:oasis:names:tc:opendocument:xmlns:container">
				<rootfiles>
					<rootfile full-path="OPS/content.opf" media-type="application/oebps-package+xml" />
				</rootfiles>
			</container>';

		return $content;
	}

	protected function getNcxToc( Book $book, $wsUrl ) {
		$content = '<?xml version="1.0" encoding="UTF-8" ?>
			<ncx xmlns="http://www.daisy.org/z3986/2005/ncx/" version="2005-1">
				<head>
					<meta name="dtb:uid" content="' . $wsUrl . '" />
					<meta name="dtb:depth" content="1" />
					<meta name="dtb:totalPageCount" content="0" />
					<meta name="dtb:maxPageNumber" content="0" />
				</head>
				<docTitle><text>' . htmlspecialchars( $book->name, ENT_QUOTES ) . '</text></docTitle>
				<docAuthor><text>' . htmlspecialchars( $book->author, ENT_QUOTES ) . '</text></docAuthor>
				<navMap>
					<navPoint id="title" playOrder="1">
						<navLabel><text>' . $this->i18n['title_page'] . '</text></navLabel>
						<content src="title.xhtml"/>
					</navPoint>';
		$order = 2;
		if( $book->content ) {
			$content .= '<navPoint id="' . $book->title . '" playOrder="' . $order . '">
						    <navLabel><text>' . htmlspecialchars( $book->name, ENT_QUOTES ) . '</text></navLabel>
						    <content src="' . $book->title . '.xhtml" />
					    </navPoint>';
			$order++;
		}
		if( !empty( $book->chapters ) ) {
			foreach( $book->chapters as $chapter ) {
				if( $chapter->name != '' ) {
					$content .= '<navPoint id="' . $chapter->title . '" playOrder="' . $order . '">
									    <navLabel><text>' . htmlspecialchars( $chapter->name, ENT_QUOTES ) . '</text></navLabel>
									    <content src="' . $chapter->title . '.xhtml" />';
					$order++;
					foreach( $chapter->chapters as $subpage ) {
						if( $subpage->name != '' ) {
							$content .= '<navPoint id="' . $subpage->title . '" playOrder="' . $order . '">
											    <navLabel><text>' . htmlspecialchars( $subpage->name, ENT_QUOTES ) . '</text></navLabel>
											    <content src="' . $subpage->title . '.xhtml" />
										</navPoint>';
							$order++;
						}
					}
					$content .= '</navPoint>';
				}
			}
		}
		$content .= '<navPoint id="about" playOrder="' . $order . '">
						<navLabel>
							<text>' . htmlspecialchars( $this->i18n['about'], ENT_QUOTES ) . '</text>
						</navLabel>
						<content src="about.xhtml"/>
					</navPoint>
			       </navMap>
			</ncx>';

		return $content;
	}

	protected function getXhtmlCover( Book $book ) {
		$content = '<div style="text-align: center; page-break-after: always;">
				    <img src="images/' . $book->pictures[$book->cover]->title . '" alt="Cover" style="height: 100%; max-width: 100%;" />
			    </div>';

		return getXhtmlFromContent( $book->lang, $content, $book->name );
	}

	protected function getXhtmlTitle( Book $book ) {
		$footerElements = array();
		if( $book->publisher != '' ) {
			$footerElements[] = $book->publisher;
		}
		if( $book->periodical != '' ) {
			$footerElements[] = $book->periodical;
		}
		if( $book->place != '' ) {
			$footerElements[] = $book->place;
		}
		if( $book->year != '' ) {
			$footerElements[] = $book->year;
		}

		$content = '<?xml version="1.0" encoding="UTF-8" ?>
			<!DOCTYPE html>
			<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="' . $book->lang . '">
				<head>
					<title>' . htmlspecialchars( $book->name, ENT_QUOTES ) . '</title>
					<meta http-equiv="default-style" content="application/xhtml+xml; charset=utf-8" />
					<link type="text/css" rel="stylesheet" href="main.css" />
				</head>
				<body style="background-color:ghostwhite;"><div style="text-align:center; position:absolute;">
					<h1 id="heading_id_2">' . htmlspecialchars( $book->name, ENT_QUOTES ) . '</h1>
					<h2>' . htmlspecialchars( $book->author, ENT_QUOTES ) . '</h2>
					<br />
					<br />
					<img alt="" src="images/Accueil_scribe.png" />
					<br />
					<h4>' . implode( $footerElements, ', ' ) . '</h4>
					<br style="margin-top: 3em; margin-bottom: 3em; border: none; background: black; width: 8em; height: 1px; display: block;" />
					<h5>' . str_replace( '%d', strftime( '%x' ), htmlspecialchars( $this->i18n['exported_from_wikisource_the'], ENT_QUOTES ) ) . '</h5>
				</div></body>
			</html>'; //TODO: Use somthing better than strftime
		return $content;
	}

	protected function getXhtmlAbout( Book $book, $wsUrl ) {
		$list = '<ul>';
		$listBot = '<ul>';
		foreach( $book->credits as $name => $value ) {
			if( in_array( 'bot', $value['flags'] ) ) {
				$listBot .= '<li>' . htmlspecialchars( $name, ENT_QUOTES ) . "</li>\n";
			} else {
				$list .= '<li>' . htmlspecialchars( $name, ENT_QUOTES ) . "</li>\n";
			}
		}
		$list .= '</ul>';
		$listBot .= '</ul>';
		$about = getTempFile( $book->lang, 'about.xhtml' );
		if( $about == '' ) {
			$about = getXhtmlFromContent( $book->lang, $list, $this->i18n['about'] );
		} else {
			$about = str_replace( '{CONTRIBUTORS}', $list, $about );
			$about = str_replace( '{BOT-CONTRIBUTORS}', $listBot, $about );
			$about = str_replace( '{URL}', '<a href="' . $wsUrl . '">' . htmlspecialchars( $book->name, ENT_QUOTES ) . '</a>', $about );
		}

		return $about;
	}

	protected function getCss( Book $book ) {
		$css = FontProvider::getCss( $book->options['fonts'], 'fonts/' );
		$css .= getTempFile( $book->lang, 'epub.css' );

		return $css;
	}
}

/**
 * Clean and modify book content in order to epub generation
 */
class BookCleanerEpub {
	protected $book = null;
	protected $linksList = array();
	protected $version;

	public function __construct($version) {
		$this->version = $version;
	}

	public function clean( Book $book ) {
		$this->book = $book;

		$this->encodeTitles();
		$this->splitChapters();

		if( $book->content ) {
			$xPath = $this->getXPath( $book->content );
			$this->setHtmlTitle( $xPath, $book->name );
			$this->cleanHtml( $xPath );
		}
		foreach( $this->book->chapters as $chapter ) {
			$xPath = $this->getXPath( $chapter->content );
			$this->setHtmlTitle( $xPath, $chapter->name );
			$this->cleanHtml( $xPath );
			foreach( $chapter->chapters as $subpage ) {
				$xPath = $this->getXPath( $subpage->content );
				$this->setHtmlTitle( $xPath, $subpage->name );
				$this->cleanHtml( $xPath );
			}
		}
	}

	protected function splitChapters() {
		$chapters = array();
		if( $this->book->content ) {
			$main = $this->splitChapter( $this->book );
			$this->book->content = $main[0]->content;
			if( !empty( $main ) ) {
				unset( $main[0] );
				$chapters = $main;
			}
		}
		foreach( $this->book->chapters as $chapter ) {
			$chapters = array_merge( $chapters, $this->splitChapter( $chapter ) );
		}
		$this->book->chapters = $chapters;
	}

	/*
	 * Credit for the tricky part of this code: Asbjorn Grandt
	 * https://github.com/Grandt/PHPePub/blob/master/EPubChapterSplitter.php
	 */
	protected function splitChapter( $chapter ) {
		$partSize = 250000;
		$length = strlen( $chapter->content->saveXML() );
		if( $length <= $partSize ) {
			return array( $chapter );
		}

		$parts = ceil( $length / $partSize );
		$partSize = ( $length / $parts ) + 2000;

		$pages = array();

		$files = array();
		$domDepth = 0;
		$domPath = array();
		$domClonedPath = array();

		$curFile = $chapter->content->createDocumentFragment();
		$files[] = $curFile;
		$curParent = $curFile;
		$curSize = 0;

		$body = $chapter->content->getElementsbytagname( "body" );
		$node = $body->item( 0 )->firstChild;
		do {
			$nodeData = $chapter->content->saveXML( $node );
			$nodeLen = strlen( $nodeData );

			if( $nodeLen > $partSize && $node->hasChildNodes() ) {
				$domPath[] = $node;
				$domClonedPath[] = $node->cloneNode( false );
				$domDepth++;

				$node = $node->firstChild;

				$nodeData = $chapter->content->saveXML( $node );
				$nodeLen = strlen( $nodeData );
			}

			$next_node = $node->nextSibling;

			if( $node != null && $node->nodeName != "#text" ) {
				if( $curSize > 0 && $curSize + $nodeLen > $partSize ) {
					$curFile = $chapter->content->createDocumentFragment();
					$files[] = $curFile;
					$curParent = $curFile;
					if( $domDepth > 0 ) {
						reset( $domPath );
						reset( $domClonedPath );
						while( list( $k, $v ) = each( $domClonedPath ) ) {
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
			while( $node == null && $domDepth > 0 ) {
				$domDepth--;
				$node = end( $domPath )->nextSibling;
				array_pop( $domPath );
				array_pop( $domClonedPath );
				if( $curParent->parentNode ) {
					$curParent = $curParent->parentNode;
				}
			}
		} while( $node != null );

		foreach( $files as $idx => $file ) {
			$xml = $this->getEmptyDom();
			$body = $xml->getElementsByTagName( "body" )->item( 0 );
			$body->appendChild( $xml->importNode( $file, true ) );
			$page = new Page();
			if( $idx == 0 ) {
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
		$this->book->title = $this->encode( $this->book->title );
		$this->linksList[] = $this->book->title . '.xhtml';
		foreach( $this->book->chapters as $chapter ) {
			$chapter->title = $this->encode( $chapter->title );
			$this->linksList[] = $chapter->title . '.xhtml';
			foreach( $chapter->chapters as $subpage ) {
				$subpage->title = $this->encode( $subpage->title );
				$this->linksList[] = $subpage->title . '.xhtml';
			}
		}
		foreach( $this->book->pictures as $picture ) {
			$picture->title = $this->encode( $picture->title );
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
		$dom->loadXML( getXhtmlFromContent( $this->book->lang, '' ) );

		return $dom;
	}

	protected function encode( $string ) {
		static $map = array();
		static $num = 0;
		$string = str_replace( ' ', '_', $string );
		if( isset( $map[$string] ) ) {
			return $map[$string];
		}
		$map[$string] = $string;
		$search = array( '[αάàâäΑÂÄ]', '[βΒ]', '[Ψç]', '[δΔ]', '[εéèêëΕÊË]', '[η]', '[φϕΦ]', '[γΓ]', '[θΘ]', '[ιîïΙÎÏ]', '[Κκ]', '[λΛ]', '[μ]', '[ν]', '[οôöÔÖ]', '[Ωω]', '[πΠ]', '[Ψψ]', '[ρΡ]', '[σΣ]', '[τ]', '[υûùüΥÛÜ]', '[ξΞ]', '[ζΖ]', '[ ]', '[^a-zA-Z0-9_\.]' );
		$replace = array( 'a', 'b', 'c', 'd', 'e', 'eh', 'f', 'g', 'h', 'i', 'k', 'l', 'm', 'n', 'o', 'oh', 'p', 'ps', 'r', 's', 't', 'u', 'x', 'z', '_', '_' );
		mb_regex_encoding( 'UTF-8' );
		foreach( $search as $i => $pat ) {
			$map[$string] = mb_eregi_replace( $pat, $replace[$i], $map[$string] );
		}
		$map[$string] = 'c' . $num . '_' . $this->cutFilename( utf8_decode( $map[$string] ) );
		$num++;

		return $map[$string];
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
		$title = $xPath->query( '/html:html/html:head/html:title' )->item( 0 );
		$title->nodeValue = $name;
	}

	/**
	 * change the picture links
	 */
	protected function setPictureLinks( DOMXPath $xPath ) {
		$list = $xPath->query( '//html:img' );
		foreach( $list as $node ) {
			$title = $this->encode( $node->getAttribute( 'alt' ) );
			if( in_array( $title, $this->linksList ) ) {
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
		$title = Api::mediawikiUrlEncode( $this->book->title );
		foreach( $list as $node ) {
			$href = $node->getAttribute( 'href' );
			$title = $this->encode( $node->getAttribute( 'title' ) ) . '.xhtml';
			if( $href[0] == '#' ) {
				continue;
			} elseif( in_array( $title, $this->linksList ) ) {
				$pos = strpos( $href, '#' );
				if( $pos !== false ) {
					$anchor = substr( $href, $pos + 1 );
					if( is_numeric( $anchor ) ) {
						$title .= '#_' . $anchor;
					} else {
						$title .= '#' . $anchor;
					}
				}
				$node->setAttribute( 'href', $title );
			} elseif( $href[0] == '/' ) {
				$node->setAttribute( 'href', 'http:' . $href );
			}
		}
	}

	/**
	 * Cut a filename if it is too long but kepp the extension
	 */
	protected function cutFilename( $string, $max = 100 ) {
		$length = strlen( $string );
		if( $length > $max ) {
			$string = substr( $string, $length - $max, $length - 1 );
		}

		return $string;
	}

	protected function addEpubTypeTags( DOMXPath $xPath ) {
		if($this->version < 3) {
			return;
		}

		$this->addTypeWithXPath( $xPath, '//html:*[contains(@class, "reference")]/html:a', 'noteref' );
		$this->addTypeWithXPath( $xPath, '//html:*[contains(@class, "references")]/html:li', 'footnote' );
	}

	protected function addTypeWithXPath( DOMXPath $xPath, $query, $type ) {
		$nodes = $xPath->query( $query );
		foreach( $nodes as $node ) {
			$node->setAttributeNS( 'http://www.idpf.org/2007/ops', 'epub:type', $type );
		}
	}
}
