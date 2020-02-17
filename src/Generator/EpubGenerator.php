<?php

namespace App\Generator;

use App\Book;
use App\Cleaner\BookCleanerEpub;
use App\FontProvider;
use App\Util\Util;
use Exception;
use ZipArchive;

/**
 * @author Thomas Pellissier Tanon
 * @copyright 2011 Thomas Pellissier Tanon
 * @license GPL-2.0-or-later
 */

/**
 * create an epub file
 */
abstract class EpubGenerator implements FormatGenerator {

	/**
	 * array key/value that contain translated strings
	 */
	protected $i18n = [];

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
	abstract protected function getVersion();

	/**
	 * create the file
	 * @param Book $book the content of the book
	 * @return string
	 */
	public function create( Book $book ) {
		$oldBookTitle = $book->title;
		$css = $this->getCss( $book );
		$this->i18n = Util::getI18n( $book->lang );
		setlocale( LC_TIME, $book->lang . '_' . strtoupper( $book->lang ) . '.utf8' );
		$wsUrl = Util::wikisourceUrl( $book->lang, $book->title );
		$cleaner = new BookCleanerEpub( $this->getVersion() );
		$cleaner->clean( $book, Util::wikisourceUrl( $book->lang ) );
		$fileName = Util::buildTemporaryFileName( $book->title, 'epub' );
		$zip = $this->createZipFile( $fileName );
		$zip->addFromString( 'META-INF/container.xml', $this->getXmlContainer() );
		$zip->addFromString( 'OPS/content.opf', $this->getOpfContent( $book, $wsUrl ) );
		$zip->addFromString( 'OPS/toc.ncx', $this->getNcxToc( $book, $wsUrl ) );
		$zip->addFromString( 'OPS/title.xhtml', $this->getXhtmlTitle( $book ) );
		$zip->addFromString( 'OPS/about.xhtml', $this->getXhtmlAbout( $book, $wsUrl ) );
		$dir = dirname( __DIR__, 2 ) . '/resources';
		$zip->addFile( $dir . '/images/Accueil_scribe.png', 'OPS/images/Accueil_scribe.png' );

		$font = FontProvider::getData( $book->options['fonts'] );
		if ( $font !== null ) {
			foreach ( $font['otf'] as $name => $path ) {
				$zip->addFile( $dir . '/fonts/' . $font['name'] . '/' . $path, 'OPS/fonts/' . $font['name'] . $name . '.otf' );
			}
		}

		if ( $book->content ) {
			$zip->addFromString( 'OPS/' . $book->title . '.xhtml', $book->content->saveXML() );
		}
		if ( !empty( $book->chapters ) ) {
			foreach ( $book->chapters as $chapter ) {
				$zip->addFromString( 'OPS/' . $chapter->title . '.xhtml', $chapter->content->saveXML() );
				foreach ( $chapter->chapters as $subpage ) {
					$zip->addFromString( 'OPS/' . $subpage->title . '.xhtml', $subpage->content->saveXML() );
				}
			}
		}
		foreach ( $book->pictures as $picture ) {
			$picture->saveToZip( $zip, 'OPS/images/' . $picture->title );
		}
		$zip->addFromString( 'OPS/main.css', $css );
		$this->addContent( $book, $zip );
		$book->title = $oldBookTitle;

		$zip->close();
		return $fileName;
	}

	/**
	 * add extra content to the file
	 */
	abstract protected function addContent( Book $book, ZipArchive $zip );

	/**
	 * return the OPF descrition file
	 * @param $book Book
	 * @param $wsUrl string URL to the main page in Wikisource
	 */
	abstract protected function getOpfContent( Book $book, $wsUrl );

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
		if ( $book->content ) {
			$content .= '<navPoint id="' . $book->title . '" playOrder="' . $order . '">
						    <navLabel><text>' . htmlspecialchars( $book->name, ENT_QUOTES ) . '</text></navLabel>
						    <content src="' . $book->title . '.xhtml" />
					    </navPoint>';
			$order++;
		}
		if ( !empty( $book->chapters ) ) {
			foreach ( $book->chapters as $chapter ) {
				if ( $chapter->name != '' ) {
					$content .= '<navPoint id="' . $chapter->title . '" playOrder="' . $order . '">
									    <navLabel><text>' . htmlspecialchars( $chapter->name, ENT_QUOTES ) . '</text></navLabel>
									    <content src="' . $chapter->title . '.xhtml" />';
					$order++;
					foreach ( $chapter->chapters as $subpage ) {
						if ( $subpage->name != '' ) {
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

	protected function getCover( Book $book ) {
		foreach ( $book->pictures as $pictureId => $picture ) {
			if ( $book->cover === $pictureId ) {
				return $picture;
			}
		}
		return null;
	}

	protected function getXhtmlTitle( Book $book ) {
		$footerElements = [];
		if ( $book->publisher != '' ) {
			$footerElements[] = $book->publisher;
		}
		if ( $book->periodical != '' ) {
			$footerElements[] = $book->periodical;
		}
		if ( $book->place != '' ) {
			$footerElements[] = $book->place;
		}
		if ( $book->year != '' ) {
			$footerElements[] = $book->year;
		}

		$content = '<?xml version="1.0" encoding="UTF-8" ?>
			<!DOCTYPE html>
			<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="' . $book->lang . '" dir="' . Util::getLanguageDirection( $book->lang ) . '">
				<head>
					<title>' . htmlspecialchars( $book->name, ENT_QUOTES ) . '</title>
				</head>
				<body style="background-color: ghostwhite; text-align: center; margin-right: auto; margin-left: auto; text-indent: 0;">
					<h2>' . htmlspecialchars( $book->name, ENT_QUOTES ) . '</h2>
					<h3>' . htmlspecialchars( $book->author, ENT_QUOTES ) . '</h3>
					<br />
					<img alt="" src="images/Accueil_scribe.png" />
					<br />
					<h5>' . implode( $footerElements, ', ' ) . '</h5>
					<br />
					<h6>' . str_replace( '%d', strftime( '%x' ), htmlspecialchars( $this->i18n['exported_from_wikisource_the'], ENT_QUOTES ) ) . '</h6>
				</body>
			</html>'; // TODO: Use something better than strftime
		return $content;
	}

	protected function getXhtmlAbout( Book $book, $wsUrl ) {
		$list = '<ul>';
		$listBot = '<ul>';
		foreach ( $book->credits as $name => $value ) {
			if ( in_array( 'bot', $value['flags'] ) ) {
				$listBot .= '<li>' . htmlspecialchars( $name, ENT_QUOTES ) . "</li>\n";
			} else {
				$list .= '<li>' . htmlspecialchars( $name, ENT_QUOTES ) . "</li>\n";
			}
		}
		$list .= '</ul>';
		$listBot .= '</ul>';
		$about = Util::getTempFile( $book->lang, 'about.xhtml' );
		if ( $about == '' ) {
			$about = Util::getXhtmlFromContent( $book->lang, $list, $this->i18n['about'] );
		} else {
			$about = str_replace( '{CONTRIBUTORS}', $list, $about );
			$about = str_replace( '{BOT-CONTRIBUTORS}', $listBot, $about );
			$about = str_replace( '{URL}', '<a href="' . $wsUrl . '">' . htmlspecialchars( $book->name, ENT_QUOTES ) . '</a>', $about );
		}

		return $about;
	}

	protected function getCss( Book $book ) {
		$css = FontProvider::getCss( $book->options['fonts'], 'fonts/' );
		$css .= Util::getTempFile( $book->lang, 'epub.css' );

		return $css;
	}

	private function createZipFile( $fileName ) {
		// This is a simple ZIP file with only the uncompressed "mimetype" file with as value "application/epub+zip"
		file_put_contents( $fileName, base64_decode( "UEsDBBQAAAAAAPibYUhvYassFAAAABQAAAAIAAAAbWltZXR5cGVhcHBsaWNhdGlvbi9lcHViK3ppcFBLAQIAABQAAAAAAPibYUhvYassFAAAABQAAAAIAAAAAAAAAAAAIAAAAAAAAABtaW1ldHlwZVBLBQYAAAAAAQABADYAAAA6AAAAAAA=" ) );
		$zip = new ZipArchive();
		if ( $zip->open( $fileName, ZipArchive::CREATE ) !== true ) {
			throw new Exception( 'Unnable to open the ZIP file ' . $fileName );
		}
		return $zip;
	}
}
