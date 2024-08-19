<?php

namespace App;

use App\Repository\CreditRepository;
use App\Util\Api;
use App\Util\Util;
use DOMDocument;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Response;

/**
 * provide all the data needed to create a book file
 */
class BookProvider {
	protected $api = null;
	protected $options = [
		'images' => true, 'fonts' => null, 'categories' => true, 'credits' => true
	];
	private $creditRepo;

	/** @var FileCache */
	private $fileCache;

	/**
	 * @param Api $api
	 * @param bool[] $options
	 * @param CreditRepository $creditRepo
	 * @param FileCache $fileCache
	 */
	public function __construct( Api $api, array $options, CreditRepository $creditRepo, FileCache $fileCache ) {
		$this->api = $api;
		$this->options = array_merge( $this->options, $options );
		$this->creditRepo = $creditRepo;
		$this->fileCache = $fileCache;
	}

	/**
	 * return all the data on a book needed to export it
	 * @param $title string the title of the main page of the book in Wikisource
	 * @param $isMetadata bool only retrieve metadata on the book
	 * @return Book
	 */
	public function get( $title, $isMetadata = false ) {
		$title = str_replace( ' ', '_', trim( $title ) );
		$doc = $this->getDocument( $title );

		return $this->getMetadata( $title, $isMetadata, $doc );
	}

	public function getMulti( array $titles, $isMetadata = false ) {
		$pages = [];
		foreach ( $titles as $title ) {
			$page = new Page();
			$page->title = str_replace( ' ', '_', trim( $title ) );
			$pages[] = $page;
		}

		$pages = $this->getPages( $pages );

		foreach ( $pages as $id => $page ) {
			$pages[$id] = $this->getMetadata( $page->title, $isMetadata, $page->content );
		}

		return $pages;
	}

	/**
	 * Get metadata etc. from a XHTML document.
	 * @param string $title The book's titlepage's page name.
	 * @param bool $isMetadata Whether this the book's content, chapters, credits, and pictures should also be extracted from the document.
	 * @param DOMDocument $doc The document to read metadata from.
	 * @return Book
	 */
	public function getMetadata( string $title, bool $isMetadata, DOMDocument $doc ): Book {
		$pageList = [ $title ];
		$parser = new PageParser( $doc );
		$book = new Book();
		$book->options = $this->options;
		$book->title = $title;
		$book->lang = $this->api->getLang();

		$metadataSrc = $parser->getMetadata( 'ws-metadata' );
		if ( $metadataSrc == '' ) {
			$metadataSrc = $title;
			$metadataParser = $parser;
		} else {
			$doc = $this->getDocument( $metadataSrc );
			$metadataParser = new PageParser( $doc );
		}

		$book->type = $metadataParser->getMetadata( 'ws-type' );
		$book->name = $metadataParser->getMetadata( 'ws-title' );
		if ( $book->name == '' ) {
			$book->name = $this->removeNamespacesFromTitle( str_replace( '_', ' ', $metadataSrc ) );
		}
		$book->periodical = $metadataParser->getMetadata( 'ws-periodical' );
		$book->author = $metadataParser->getMetadata( 'ws-author' );
		$book->translator = $metadataParser->getMetadata( 'ws-translator' );
		$book->illustrator = $metadataParser->getMetadata( 'ws-illustrator' );
		$book->school = $metadataParser->getMetadata( 'ws-school' );
		$book->publisher = $metadataParser->getMetadata( 'ws-publisher' );
		$book->year = $metadataParser->getMetadata( 'ws-year' );
		$book->place = $metadataParser->getMetadata( 'ws-place' );
		$book->key = $metadataParser->getMetadata( 'ws-key' );
		$book->progress = $metadataParser->getMetadata( 'ws-progress' );
		$book->volume = $metadataParser->getMetadata( 'ws-volume' );
		$book->scan = str_replace( ' ', '_', $metadataParser->getMetadata( 'ws-scan' ) );
		$pictures = [];
		if ( $this->options['images'] || $isMetadata ) {
			$cover = $this->getCover( $metadataParser->getMetadata( 'ws-cover' ) );
			if ( $cover instanceof Picture ) {
				$book->cover = $cover->title;
				$pictures[$book->cover] = $cover;
			}
		}
		if ( $this->options['categories'] ) {
			$book->categories = $this->getCategories( $metadataSrc );
		}
		$pageTitles = $parser->getPagesList();
		$namespaces = array_keys( $this->api->getNamespaces() );

		if ( !$isMetadata ) {
			if ( !$parser->metadataIsSet( 'ws-noinclude' ) ) {
				$book->content = $parser->getContent( true );
				if ( $this->options['images'] ) {
					$pictures = array_merge( $pictures, $parser->getPicturesList() );
				}
			}
			$chapterTitles = $parser->getFullChaptersList( $title, $pageList, $namespaces );
			$chapters = $this->getPages( $chapterTitles );

			// Generate all the chapters
			foreach ( $chapters as $chapter_key => $chapter ) {
				$parser = new PageParser( $chapter->content );
				if ( $parser->metadataIsSet( 'ws-noinclude' ) ) {
					unset( $chapters[$chapter_key] );
					continue;
				}
				$pageTitles = array_merge( $pageTitles, $parser->getPagesList() );
				$chapter->content = $parser->getContent( false );
				if ( $this->options['images'] ) {
					$pictures = array_merge( $pictures, $parser->getPicturesList() );
				}
				$subpagesTitles = $parser->getChaptersList( $pageList, $namespaces );
				if ( !empty( $subpagesTitles ) ) {
					$subpages = $this->getPages( $subpagesTitles );
					foreach ( $subpages as $subpage_key => $subpage ) {
						$parser = new PageParser( $subpage->content );
						if ( $parser->metadataIsSet( 'ws-noinclude' ) ) {
							unset( $chapters[$subpage_key] );
							continue;
						}
						$pageTitles = array_merge( $pageTitles, $parser->getPagesList() );
						$subpage->content = $parser->getContent( false );
						if ( $this->options['images'] ) {
							$pictures = array_merge( $pictures, $parser->getPicturesList() );
						}
					}
					$chapterTitles = array_merge( $chapterTitles, $subpagesTitles );
					$chapter->chapters = $subpages;
				}
			}
			$book->chapters = $chapters;

			if ( $this->options['credits'] ) {
				$book->credits = $this->getBookCredits( $book, $chapterTitles, $pageTitles, $pictures );
			}

			$pictures = $this->getPicturesData( $pictures );
		}
		$book->pictures = $pictures;

		return $book;
	}

	/**
	 * return the content of the page
	 * @param string $title the title of the page in Wikisource
	 * @return DOMDocument
	 */
	protected function getDocument( $title ) {
		return $this->domDocumentFromHtml( $this->api->getPageAsync( $title )->wait() );
	}

	protected function domDocumentFromHtml( $html ) {
		return Util::buildDOMDocumentFromHtml( $html );
	}

	/**
	 * return the content of the page
	 * @param Page[] $pages
	 * @return Page[]
	 */
	protected function getPages( $pages ) {
		$pageTitles = [];
		foreach ( $pages as $id => $page ) {
			$pageTitles[$id] = $page->title;
		}
		$contents = $this->api->getPageBatch( $pageTitles );
		foreach ( $pages as $id => $page ) {
			$page->content = isset( $contents[$id] )
				? $this->domDocumentFromHtml( $contents[$id] ) : null;
		}
		return $pages;
	}

	/**
	 * Download image files to the temp directory, and add `tempFile` and `mimetype` attributes to each Picture object.
	 * @param Picture[] $pictures All Pictures in the book, keyed by the thumbnail filename.
	 * @return Picture[]
	 */
	protected function getPicturesData( array $pictures ) {
		$client = $this->api->getClient();
		$requests = function () use ( $client, $pictures ) {
			foreach ( $pictures as $picture ) {
				$url = $picture->url;
				// Clean up the image URLs if they're protocol relative or only a path. This would probably be better in
				// PageParser, but it doesn't have access to the domain name.
				if ( str_starts_with( $url, '//' ) ) {
					$url = 'https:' . $url;
				} elseif ( str_starts_with( $url, '/' ) ) {
					$url = 'https://' . $this->api->getDomainName() . $url;
				}
				$picture->url = $url;
				yield function () use ( $client, $url ) {
					// We could use the 'sink' option here, but for https://github.com/Kevinrob/guzzle-cache-middleware/issues/82
					// @phan-suppress-next-line PhanUndeclaredMethod Magic method not declared in the interface
					return $client->getAsync( $url );
				};
			}
		};
		$pool = new Pool( $client, $requests(), [
			'fulfilled' => function ( Response $response, $index ) use ( $pictures ) {
				$pictureIndex = array_keys( $pictures )[ $index ];

				// Write the temp file and store its path.
				$tempFile = $this->fileCache->getDirectory() . '/' . uniqid( 'pic-' );
				file_put_contents( $tempFile, $response->getBody()->getContents() );
				$pictures[$pictureIndex]->tempFile = $tempFile;

				// Get the media type, and strip everything apart from the main type and subtype, to extract a mime
				// type that conforms to https://www.w3.org/publishing/epub32/epub-spec.html#sec-cmt-supported
				$contentType = $response->getHeader( 'Content-Type' )[0];
				if ( strpos( $contentType, ';' ) !== false ) {
					$contentType = substr( $contentType, 0, strpos( $contentType, ';' ) );
				}
				// Store the returned mime type of the downloaded file in the Picture object.
				$pictures[$pictureIndex]->mimetype = $contentType;
			},
		] );
		$pool->promise()->wait();
		return $pictures;
	}

	/**
	 * return the categories in the pages
	 * @param string $title the title of the page in Wikisource
	 * @return string[] The categories
	 */
	public function getCategories( $title ) {
		$categories = [];
		$response = $this->api->queryAsync( [ 'titles' => $title, 'prop' => 'categories', 'clshow' => '!hidden' ] )->wait();
		foreach ( $response['query']['pages'] as $list ) {
			if ( isset( $list['categories'] ) ) {
				foreach ( $list['categories'] as $categorie ) {
					$cat = explode( ':', $categorie['title'], 2 );
					$categories[] = $cat[1];
				}
			}
		}

		return $categories;
	}

	/**
	 * Return the cover of the book.
	 * @param string $cover The title of the cover without 'File', e.g. Lorem.pdf/3
	 * @return ?Picture The cover picture, or null if it could not be determined.
	 */
	public function getCover( $cover ): ?Picture {
		if ( trim( $cover ) === '' ) {
			return null;
		}
		$id = explode( '/', $cover );
		$title = $id[0];
		$picture = new Picture();
		$picture->title = $cover;

		$pageNum = 1;
		if ( array_key_exists( 1, $id ) && intval( $id[1] ) ) {
			$pageNum = intval( $id[1] );
		}
		$width = 400;
		$urlParam = '';
		if ( in_array( pathinfo( $title, PATHINFO_EXTENSION ), [ 'pdf', 'djvu' ] ) ) {
			$urlParam = 'page' . $pageNum . '-' . $width . 'px';
		}
		$response = $this->api->queryAsync( [
			'titles' => 'File:' . $title,
			'prop' => 'imageinfo',
			'iiprop' => 'thumbmime|dimensions|url|canonicaltitle',
			'iiurlparam' => $urlParam,
			'iiurlwidth' => $width,
			'formatversion' => 2,
		] )->wait();
		// Give up for invalid cover titles or those that do not exist.
		if ( !isset( $response['query']['pages'] )
			|| isset( $response['query']['pages'][0]['missing'] )
			|| isset( $response['query']['pages'][0]['invalid'] )
		) {
			return null;
		}

		$page = end( $response['query']['pages'] );
		$iinfo = $page['imageinfo'][0];

		$picture->url = $iinfo['thumburl'];
		$picture->mimetype = $iinfo['thumbmime'];
		$picture->name = $iinfo['canonicaltitle'];

		$picture->title = $iinfo['canonicaltitle'];
		// if these are different, the thumb isn't the same as the canonicalTitle
		// so add the extension from the URL
		if ( $iinfo['mime'] !== $iinfo['thumbmime'] ) {
			$picture->title .= preg_replace( '/.*(?=\.[^.]+$)/', '', $picture->url );
		}

		return $picture;
	}

	/**
	 * @param Book $book
	 * @param Page[] $chapters
	 * @param string[] $otherPages
	 * @param Picture[] $pictures
	 * @return array
	 */
	protected function getBookCredits( Book $book, array $chapters, array $otherPages, array $pictures ) {
		$namespaces = $this->api->getNamespaces();

		$pages = [ $book->title ];
		foreach ( $chapters as $id => $chapter ) {
			$pages[] = $chapter->title;
		}
		if ( $book->scan != '' ) {
			$pages[] = 'Index:' . $book->scan;
		}

		$pages = array_unique( array_merge( $pages, $otherPages ) );
		$pageCredits = $this->creditRepo->getPageCredits( $book->lang, $namespaces, $pages );

		$imagesSet = [];
		foreach ( $pictures as $id => $picture ) {
			if ( $picture->name ) {
				$imagesSet[$picture->name] = true;
			}
		}
		$imageCredits = [];
		if ( !empty( $imagesSet ) ) {
			$images = array_keys( $imagesSet );
			$imageCredits = $this->creditRepo->getImageCredits( $images );
		}

		$allCredits = array_merge( $pageCredits, $imageCredits );
		$credits = [];

		foreach ( $allCredits as $values ) {
			$name = $values[ 'actor_name' ];
			if ( !in_array( $name, $credits ) ) {
				$credits[$name] = [ 'count' => 0, 'bot' => [] ];
			}
			if ( isset( $values['count'] ) ) {
				$credits[$name]['count'] += $values['count'];
			} else {
				$credits[$name]['count'] += 1;
			}
			$credits[$name]['bot'] = $values['bot'];
		}

		uasort( $credits, function ( $a, $b ) {
			return $b['count'] - $a['count'];
		} );

		return $credits;
	}

	private function removeNamespacesFromTitle( $title ) {
		$namespaces = array_keys( $this->api->getNamespaces() );
		foreach ( $namespaces as $namespace ) {
			if ( strpos( $title, $namespace . ':' ) === 0 ) {
				return substr( $title, strlen( $namespace ) + 1 );
			}
		}
		return $title;
	}
}
