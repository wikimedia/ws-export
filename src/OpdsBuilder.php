<?php

namespace App;

use App\Generator\AtomGenerator;
use App\Util\Api;
use App\Util\Util;
use DOMDocument;
use DOMElement;
use Exception;

/**
 * @author Thomas Pellissier Tanon
 * @copyright 2011 Thomas Pellissier Tanon
 * @license GPL-2.0-or-later
 */

/**
 * Allows to build OPDS feeds
 */
class OpdsBuilder {

	/** @var FileCache */
	private $fileCache;

	/**
	 * @var string
	 */
	private $exportBasePath;

	/**
	 * @var BookProvider
	 */
	private $bookProvider;

	/** @var string */
	private $lang;

	/** @var Api */
	private $api;

	/**
	 * @param BookProvider $bookProvider
	 * @param Api $api
	 * @param string $lang
	 * @param FileCache $fileCache
	 * @param string $exportBasePath
	 */
	public function __construct( BookProvider $bookProvider, Api $api, string $lang, FileCache $fileCache, $exportBasePath = '' ) {
		$this->bookProvider = $bookProvider;
		$this->api = $api;
		$this->lang = $lang;
		$this->fileCache = $fileCache;
		$this->exportBasePath = $exportBasePath;
	}

	/**
	 * @param string $categoryTitle
	 * @return bool|string
	 * @throws Exception
	 */
	public function buildFromCategory( $categoryTitle ) {
		$response = $this->api->completeQuery( [
			'generator' => 'categorymembers',
			'gcmtitle' => $categoryTitle,
			'gcmnamespace' => '0',
			'prop' => 'info',
			'gcmlimit' => '100',
		] );
		if ( !array_key_exists( 'query', $response ) ) {
			throw new Exception( "Category not found: $categoryTitle" );
		}

		$pages = $response['query']['pages'];

		$titles = [];
		foreach ( $pages as $page ) {
			$titles[] = $page['title'];
		}

		return $this->buildFromTitles( $titles, $categoryTitle );
	}

	private function buildFromTitles( array $titles, $fromPage = '' ) {
		$generator = new AtomGenerator( $this->fileCache );
		$generator->setExportBasePath( $this->exportBasePath );

		$dom = new DOMDocument( '1.0', 'UTF-8' );
		$feed = $dom->createElement( 'feed' );
		$generator->appendNamespaces( $feed );
		$feed->setAttribute( 'xml:lang', $this->lang );
		$this->addNode( $dom, $feed, 'title', $fromPage );
		$this->addNode( $dom, $feed, 'updated', date( DATE_ATOM ) );
		if ( $fromPage !== '' ) {
			$wsUrl = Util::wikisourceUrl( $this->lang, $fromPage );
			$this->addNode( $dom, $feed, 'id', $wsUrl, 'dcterms:URI' );
			$this->addLink( $dom, $feed, 'alternate', $wsUrl, 'text/html' );
		}

		$options = [ 'categories' => false, 'images' => false ];
		foreach ( array_chunk( $titles, 5 ) as $chunk ) {
			foreach ( $this->bookProvider->getMulti( $chunk, $options, true ) as $book ) {
				$entry = $generator->buildEntry( $book, $dom );
				$feed->appendChild( $entry );
			}
		}

		$dom->appendChild( $feed );

		return $dom->saveXML();
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

	private function addLink( DOMDocument $dom, DOMElement $head, $rel, $href, $type = '' ) {
		$node = $dom->createElement( 'link' );
		$node->setAttribute( 'rel', $rel );
		if ( $type !== '' ) {
			$node->setAttribute( 'type', $type );
		}
		$node->setAttribute( 'href', $href );
		$head->appendChild( $node );
	}
}
