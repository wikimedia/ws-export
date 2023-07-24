<?php

namespace App;

use DateInterval;
use DOMDocument;
use DOMElement;
use GuzzleHttp\ClientInterface;
use Psr\Cache\CacheItemInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Cache\CacheInterface;

class Wikidata {

	/** @var CacheInterface */
	private $cache;

	/** @var LoggerInterface */
	private $logger;

	/** @var ClientInterface */
	private $client;

	public function __construct( CacheInterface $cache, LoggerInterface $logger, ClientInterface $client ) {
		$this->cache = $cache;
		$this->logger = $logger;
		$this->client = $client;
	}

	/**
	 * Get names of all Wikisources.
	 * @param string $lang The language to (try to) use for the name of Multilingual Wikisource or any that don't have a localized label.
	 * @return array<string> Key is subdomain, value is the Wikisource name.
	 */
	public function getWikisourceLangs( string $lang ): array {
		return $this->cache->get( 'wikidata_wikisources_' . $lang, function ( CacheItemInterface $cacheItem ) use ( $lang ) {
			$cacheItem->expiresAfter( new DateInterval( 'P1M' ) );
			$this->logger->debug( "Requesting list of Wikisources from Wikidata" );
			$query =
				"SELECT ?item ?label ?website WHERE { "
				// Instance of Wikisource language edition but not of closed wiki.
				. "?item wdt:P31 wd:Q15156455 . "
				. "MINUS { ?item wdt:P31 wd:Q47495990 . } "
				// Wikimedia language code.
				. "?item wdt:P424 ?wikiLangCode . "
				// Label (fall back to the interface language, and then English).
				. "  OPTIONAL { ?item rdfs:label ?labelLocal FILTER( LANG(?labelLocal) = ?wikiLangCode ) } . "
				. "  OPTIONAL { ?item rdfs:label ?labelUselang FILTER( LANG(?labelUselang) = '$lang' ) } . "
				. "  ?item rdfs:label ?labelEn FILTER( LANG(?labelEn) = 'en' ) . "
				. "  BIND( IF( BOUND(?labelLocal), ?labelLocal, IF( BOUND(?labelUselang), ?labelUselang, ?labelEn ) ) AS ?label ) . "
				// Official website.
				. "?item wdt:P856 ?website . "
				. "} ORDER BY ?wikiLangCode ";
			$data = $this->fetch( $query );
			$out = [];
			foreach ( $data as $datum ) {
				// Hard-code Multilingual Wikisource, to avoid issues with incubator Wikisources
				// being given the same domain name as P856 (official website). T342520.
				if ( str_ends_with( $datum['item'], 'Q18198097' ) ) {
					$out['mul'] = $datum['label'];
					continue;
				}
				preg_match( '|https://([a-z-_]*)\.?wikisource\.org|', $datum['website'], $matches );
				$out[$matches[1]] = $datum['label'];
			}
			return $out;
		} );
	}

	/**
	 * Get the results of this query.
	 * @param string $query The Sparql query to execute.
	 * @return array<string,array<string,string>> Array of results keyed by the names given in the Sparql query.
	 */
	public function fetch( string $query ): array {
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
	protected function getXml( string $query ): DOMDocument {
		$url = "https://query.wikidata.org/bigdata/namespace/wdq/sparql?query=" . urlencode( $query );
		$response = $this->client->request( 'GET', $url );
		$dom = new DOMDocument( '1.0', 'UTF-8' );
		$xml = $response->getBody()->getContents();
		$dom->loadXML( $xml );
		return $dom;
	}

	/**
	 * Restructure the XML that comes back from the Wikidata Query Service
	 * @return array<string,string>
	 */
	protected function getBindings( DOMElement $resultNode ): array {
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
