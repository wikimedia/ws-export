<?php
/**
 * @author Thomas Pellissier Tanon
 * @copyright 2011 Thomas Pellissier Tanon
 * @licence http://www.gnu.org/licenses/gpl.html GNU General Public Licence
 */

/**
 * create an epub 2 file
 * @see http://idpf.org/epub/201
 */
class Epub2Generator extends EpubGenerator {

	protected function getVersion() {
		return 2;
	}

	protected function addContent( Book $book, ZipArchive $zip ) {
	}

	protected function getOpfContent( Book $book, $wsUrl ) {
		$content = '<?xml version="1.0" encoding="UTF-8" ?>
			<package xmlns="http://www.idpf.org/2007/opf" unique-identifier="uid" version="2.0">
				<metadata xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:opf="http://www.idpf.org/2007/opf" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:dcterms="http://purl.org/dc/terms/">
					<dc:identifier id="uid" opf:scheme="URI">' . $wsUrl . '</dc:identifier>
					<dc:language xsi:type="dcterms:RFC4646">' . $book->lang . '</dc:language>
					<dc:title>' . htmlspecialchars( $book->name, ENT_QUOTES ) . '</dc:title>
					<dc:source>' . $wsUrl . '</dc:source>
					<dc:date opf:event="ops-publication" xsi:type="dcterms:W3CDTF">' . date( DATE_W3C ) . '</dc:date>
					<dc:rights>http://creativecommons.org/licenses/by-sa/3.0/</dc:rights>
					<dc:rights>http://www.gnu.org/copyleft/fdl.html</dc:rights>
					<dc:contributor opf:role="bkp">Wikisource</dc:contributor>';
		if( $book->author != '' ) {
			$content .= '<dc:creator opf:role="aut">' . htmlspecialchars( $book->author, ENT_QUOTES ) . '</dc:creator>';
		}
		if( $book->translator != '' ) {
			$content .= '<dc:contributor opf:role="trl">' . htmlspecialchars( $book->translator, ENT_QUOTES ) . '</dc:contributor>';
		}
		if( $book->illustrator != '' ) {
			$content .= '<dc:contributor opf:role="ill">' . htmlspecialchars( $book->illustrator, ENT_QUOTES ) . '</dc:contributor>';
		}
		if( $book->publisher != '' ) {
			$content .= '<dc:publisher>' . htmlspecialchars( $book->publisher, ENT_QUOTES ) . '</dc:publisher>';
		}
		if( $book->year != '' ) {
			$content .= '<dc:date opf:event="original-publication">' . htmlspecialchars( $book->year, ENT_QUOTES ) . '</dc:date>';
		}
		if( $book->cover != '' ) {
			$content .= '<meta name="cover" content="cover" />';
		} else {
			$content .= '<meta name="cover" content="title" />';
		}
		$content .= '</metadata>
				<manifest>
					<item href="toc.ncx" id="ncx" media-type="application/x-dtbncx+xml"/>';
		if( $book->cover != '' ) {
			$content .= '<item id="cover" href="cover.xhtml" media-type="application/xhtml+xml" />';
		}
		$content .= '<item id="title" href="title.xhtml" media-type="application/xhtml+xml" />
					<item id="mainCss" href="main.css" media-type="text/css" />
					<item id="Accueil_scribe.png" href="images/Accueil_scribe.png" media-type="image/png" />';
		$font = FontProvider::getData( $book->options['fonts'] );
		if( $font !== null ) {
			foreach( $font['otf'] as $name => $path ) {
				$content .= '<item id="' . $font['name'] . $name . '" href="fonts/' . $font['name'] . $name . '.otf" media-type="font/opentype" />' . "\n";
			}
		}
		if( $book->content ) {
			$content .= '<item id="' . $book->title . '" href="' . $book->title . '.xhtml" media-type="application/xhtml+xml" />' . "\n";
		}
		foreach( $book->chapters as $chapter ) {
			$content .= '<item id="' . $chapter->title . '" href="' . $chapter->title . '.xhtml" media-type="application/xhtml+xml" />' . "\n";
			foreach( $chapter->chapters as $subpage ) {
				$content .= '<item id="' . $subpage->title . '" href="' . $subpage->title . '.xhtml" media-type="application/xhtml+xml" />' . "\n";
			}
		}
		foreach( $book->pictures as $picture ) {
			$content .= '<item id="' . $picture->title . '" href="images/' . $picture->title . '" media-type="' . $picture->mimetype . '" />' . "\n";
		}
		$content .= '<item id="about" href="about.xhtml" media-type="application/xhtml+xml" />
				</manifest>
				<spine toc="ncx">';
		if( $book->cover != '' ) {
			$content .= '<itemref idref="cover" linear="no" />';
		}
		$content .= '<itemref idref="title" linear="yes" />';
		if( $book->content ) {
			$content .= '<itemref idref="' . $book->title . '" linear="yes" />';
		}
		if( !empty( $book->chapters ) ) {
			foreach( $book->chapters as $chapter ) {
				$content .= '<itemref idref="' . $chapter->title . '" linear="yes" />';
				foreach( $chapter->chapters as $subpage ) {
					$content .= '<itemref idref="' . $subpage->title . '" linear="yes" />';
				}
			}
		}
		$content .= '<itemref idref="about" linear="yes" />
				</spine>
				<guide>';
		if( $book->cover != '' ) {
			$content .= '<reference type="cover" title="' . htmlspecialchars( $this->i18n['cover'], ENT_QUOTES ) . '" href="cover.xhtml" />';
		} else {
			$content .= '<reference type="cover" title="' . htmlspecialchars( $this->i18n['cover'], ENT_QUOTES ) . '" href="title.xhtml" />';
		}
		$content .= '<reference type="title-page" title="' . htmlspecialchars( $this->i18n['title_page'], ENT_QUOTES ) . '" href="title.xhtml" />';
		if( $book->content ) {
			$content .= '<reference type="text" title="' . htmlspecialchars( $book->name, ENT_QUOTES ) . '" href="' . $book->title . '.xhtml" />';
		}
		$content .= '<reference type="copyright-page" title="' . htmlspecialchars( $this->i18n['about'], ENT_QUOTES ) . '" href="about.xhtml" />
				</guide>
			</package>';

		return $content;
	}
}
