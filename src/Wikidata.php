<?php

namespace App;

use DateInterval;
use DOMDocument;
use DOMElement;
use GuzzleHttp\Client;
use Psr\Cache\CacheItemInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Cache\CacheInterface;

class Wikidata {

	/** @var CacheInterface */
	private $cache;

	/** @var LoggerInterface */
	private $logger;

	public function __construct( CacheInterface $cache, LoggerInterface $logger ) {
		$this->cache = $cache;
		$this->logger = $logger;
	}

	/**
	 * Get names of all Wikisources.
	 * @param string $lang The language to (try to) use for the Wikisource names.
	 * @return string[] Key is subdomain, value is the localized Wikisource name.
	 */
	public function getWikisourceLangs( string $lang ): array {
		return $this->cache->get( 'wikidata_wikisources_' . $lang, function ( CacheItemInterface $cacheItem ) use ( $lang ) {
			$cacheItem->expiresAfter( new DateInterval( 'P1M' ) );
			$this->logger->debug( "Requesting list of Wikisources from Wikidata" );
			$query =
				"SELECT ?label ?website WHERE { "
				// Instance of Wikisource language edition.
				. "?item wdt:P31 wd:Q15156455 . "
				// Label (fall back to English).
				. "  optional{ ?item rdfs:label ?labelLocal FILTER( LANG(?labelLocal) = '$lang' ) } . "
				. "  ?item rdfs:label ?labelEn FILTER( LANG(?labelEn) = 'en' ) . "
				. "  BIND( IF( BOUND(?labelLocal), ?labelLocal, ?labelEn ) AS ?label ) . "
				// Official website.
				. "?item wdt:P856 ?website . "
				. "} ORDER BY ?label ";
			$data = $this->fetch( $query );
			$out = [];
			foreach ( $data as $datum ) {
				preg_match( '|https://([a-z-_]*)\.?wikisource\.org|', $datum['website'], $matches );
				$subdomain = $matches[1];
				if ( empty( $subdomain ) ) {
					$subdomain = 'mul';
				}
				$out[$subdomain] = $datum['label'];
			}
			return $out;
		} );
	}

	/**
	 * Get the results of this query.
	 * @param string $query The Sparql query to execute.
	 * @return string[] Array of results keyed by the names given in the Sparql query.
	 */
	public function fetch( string $query ) {
		$out = [];
		$result = $this->getXml( $query );
		foreach ( $result->getElementsByTagName( 'result' ) as $res ) {
			$out[] = $this->getBindings( $res );
		}
		return $out;
	}

	/**
	 * Get the XML result of a Sparql query.
	 * @param string $query The Sparql query to execute.
	 * @return DOMDocument
	 */
	protected function getXml( $query ) {
		$url = "https://query.wikidata.org/bigdata/namespace/wdq/sparql?query=" . urlencode( $query );
		$client = new Client();
		$response = $client->request( 'GET', $url );
		$dom = new DOMDocument( '1.0', 'UTF-8' );
		$xml = $response->getBody()->getContents();
		$dom->loadXML( $xml );
		return $dom;
	}

	/**
	 * Restructure the XML that comes back from the Wikidata Query Service
	 * @return array
	 */
	protected function getBindings( DOMElement $resultNode ) {
		$out = [];
		/** @var DOMElement $binding */
		foreach ( $resultNode->getElementsByTagName( 'binding' ) as $binding ) {
			$literal = $binding->getElementsByTagName( 'literal' );
			if ( $literal->count() > 0 ) {
				$out[ $binding->getAttribute( 'name' ) ] = $literal->item( 0 )->textContent;
			}
			$uri = $binding->getElementsByTagName( 'uri' );
			if ( $uri->count() > 0 ) {
				$out[ $binding->getAttribute( 'name' ) ] = $uri->item( 0 )->textContent;
			}
		}
		return $out;
	}
}
