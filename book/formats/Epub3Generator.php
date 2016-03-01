<?php
/**
 * @author Thomas Pellissier Tanon
 * @copyright 2012 Thomas Pellissier Tanon
 * @licence http://www.gnu.org/licenses/gpl.html GNU General Public Licence
 */

/**
 * create an epub 3 file
 * @see http://idpf.org/epub/30
 * TODO Add semantic elements to html http://idpf.org/epub/30/spec/epub30-contentdocs.html
 */
class Epub3Generator extends EpubGenerator {

	protected function getVersion() {
		return 3;
	}

	protected function addContent( Book $book, ZipArchive $zip ) {
		$zip->addFromString( 'OPS/nav.xhtml', $this->getXhtmlNav( $book ) );
	}

	protected function getOpfContent( Book $book, $wsUrl ) {
		$content = '<?xml version="1.0" encoding="UTF-8" ?>
		      <package xmlns="http://www.idpf.org/2007/opf" unique-identifier="uid" version="3.0" xml:lang="' . $book->lang . '" prefix="cc: http://creativecommons.org/ns#">
			     <metadata xmlns:dc="http://purl.org/dc/elements/1.1/">
				    <dc:identifier id="uid">' . $wsUrl . '</dc:identifier>
				    <meta property="dcterms:modified">' . date( 'Y-m-d\TH:i:s' ) . 'Z</meta>
				    <dc:language>' . $book->lang . '</dc:language>
				    <dc:title id="meta-title">' . htmlspecialchars( $book->name, ENT_QUOTES ) . '</dc:title>
				    <meta refines="#meta-title" property="title-type">main</meta>
				    <dc:source>' . $wsUrl . '</dc:source>
				    <dc:rights xml:lang="en">Creative Commons BY-SA 3.0</dc:rights>
				    <link rel="cc:license" href="http://creativecommons.org/licenses/by-sa/3.0/" />
				    <dc:rights xml:lang="en">GNU Free Documentation License</dc:rights>
				    <link rel="cc:license" href="http://www.gnu.org/copyleft/fdl.html" />
				    <dc:contributor id="meta-bkp">Wikisource</dc:contributor>
				    <meta refines="#meta-bkp" property="role" scheme="marc:relators">bkp</meta>';
		if( $book->author != '' ) {
			$content .= '<dc:creator id="meta-aut">' . htmlspecialchars( $book->author, ENT_QUOTES ) . '</dc:creator>
					       <meta refines="#meta-aut" property="role" scheme="marc:relators">aut</meta>';
		}
		if( $book->translator != '' ) {
			$content .= '<dc:contributor id="meta-trl">' . htmlspecialchars( $book->translator, ENT_QUOTES ) . '</dc:contributor>
					       <meta refines="#meta-trl" property="role" scheme="marc:relators">trl</meta>';
		}
		if( $book->illustrator != '' ) {
			$content .= '<dc:contributor id="meta-ill">' . htmlspecialchars( $book->illustrator, ENT_QUOTES ) . '</dc:contributor>
					       <meta refines="#meta-ill" property="role" scheme="marc:relators">ill</meta>';
		}
		if( $book->publisher != '' ) {
			$content .= '<dc:publisher>' . htmlspecialchars( $book->publisher, ENT_QUOTES ) . '</dc:publisher>';
		}
		if( $book->year != '' ) {
			$content .= '<dc:date>' . htmlspecialchars( $book->year, ENT_QUOTES ) . '</dc:date>';
		}
		if( $book->cover != '' ) {
			$content .= '<meta name="cover" content="cover" />';
		} else {
			$content .= '<meta name="cover" content="title" />';
		}
		$content .= '</metadata>
			     <manifest>
				    <item href="nav.xhtml" id="nav" media-type="application/xhtml+xml" properties="nav" />
				    <item href="toc.ncx" id="ncx" media-type="application/x-dtbncx+xml"/>'; //deprecated
		if( $book->cover != '' ) { //TODO use image ?
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
		foreach( $book->pictures as $pictureId => $picture ) {
			$content .= '<item id="' . $picture->title . '" href="images/' . $picture->title . '" media-type="' . $picture->mimetype . '"';
			if( $book->cover === $pictureId ) {
				$content .= ' properties="cover-image"';
			}
			$content .= ' />' . "\n";
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
		      </package>';

		return $content;
	}

	protected function getXhtmlNav( Book $book ) {
		$content = '<?xml version="1.0" encoding="UTF-8" ?>
		      <!DOCTYPE html>
		      <html xmlns="http://www.w3.org/1999/xhtml" xmlns:epub="http://www.idpf.org/2007/ops" xml:lang="' . $book->lang . '">
			     <head>
				    <title>' . htmlspecialchars( $book->name, ENT_QUOTES ) . '</title>
				    <meta charset="utf-8" />
				    <link type="text/css" rel="stylesheet" href="main.css" />
			     </head>
			     <body>
				    <section epub:type="frontmatter toc">
					   <nav epub:type="toc" id="toc">
						  <ol>
							 <li id="toc-title">
								<a href="title.xhtml">' . htmlspecialchars( $this->i18n['title_page'], ENT_QUOTES ) . '</a>
							 </li>';
		if( $book->content ) {
			$content .= '<li id="toc-' . $book->title . '">
							 <a href="' . $book->title . '.xhtml">' . htmlspecialchars( $book->name, ENT_QUOTES ) . '</a>
						  </li>';
		}
		if( !empty( $book->chapters ) ) {
			foreach( $book->chapters as $chapter ) {
				if( $chapter->name != '' ) {
					$content .= '<li id="toc-' . $chapter->title . '">
								<a href="' . $chapter->title . '.xhtml">' . htmlspecialchars( $chapter->name, ENT_QUOTES ) . '</a>';
					if( !empty( $chapter->chapters ) ) {
						$content .= '<ol>';
						foreach( $chapter->chapters as $subpage ) {
							if( $subpage->name != '' ) {
								$content .= '<li id="toc-' . $subpage->title . '">
										      <a href="' . $subpage->title . '.xhtml">' . htmlspecialchars( $subpage->name, ENT_QUOTES ) . '</a>
									       </li>';
							}
						}
						$content .= '</ol>';
					}
					$content .= '</li>';
				}
			}
		}
		$content .= '<li id="toc-about">
								<a href="about.xhtml">' . htmlspecialchars( $this->i18n['about'], ENT_QUOTES ) . '</a>
							 </li>
						  </ol>
					   </nav>
					   <nav epub:type="landmarks" id="guide">
						  <ol>
							    <li>
								  <a epub:type="toc" href="#toc">Table of Contents</a>
							    </li>
							    <li>
								  <a epub:type="bodymatter" href="' . $book->title . '.xhtml">' . htmlspecialchars( $book->name, ENT_QUOTES ) . '</a>
							    </li>
							    <li>
								  <a epub:type="copyright-page" href="about.xhtml">' . htmlspecialchars( $this->i18n['about'], ENT_QUOTES ) . '</a>
							    </li>
						  </ol>
					    </nav>
				      </section>
			        </body>
		      </html>';

		return $content;
	}
}
