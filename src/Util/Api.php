<?php

namespace App\Util;

use App\Exception\WsExportException;
use App\PageParser;
use DateInterval;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Promise\PromiseInterface;
use Kevinrob\GuzzleCache\CacheMiddleware;
use Kevinrob\GuzzleCache\Storage\Psr6CacheStorage;
use Kevinrob\GuzzleCache\Strategy\GreedyCacheStrategy;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Cache\Adapter\NullAdapter;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * a base class for communications with Wikisource
 */
class Api {
	private const USER_AGENT = 'Wikisource Export/0.1';
	private const CONNECT_TIMEOUT = 10; // in seconds
	private const REQUEST_TIMEOUT = 60; // in seconds

	/** @var string */
	private $lang = '';

	/** @var string */
	private $domainName = '';

	/**
	 * @var ClientInterface
	 */
	private $client;

	/** @var LoggerInterface */
	private $logger;

	/**
	 * @todo Use PSR6 cache ($this->cacheItemPool) here instead of this
	 * because we have to use it for Guzzle and it'd be simpler to not have two caches.
	 * @var CacheInterface
	 */
	private $cache;

	/** @var CacheItemPoolInterface */
	private $cacheItemPool;

	/** @var int */
	private $cacheTtl;

	/** @var string[][] */
	private $namespaces = [];

	public function __construct( LoggerInterface $logger, CacheItemPoolInterface $cacheItemPool, CacheInterface $cache, int $cacheTtl ) {
		$this->logger = $logger;
		$this->cache = $cache;
		$this->cacheItemPool = $cacheItemPool;
		$this->cacheTtl = $cacheTtl;
	}

	/**
	 * Set the Wikisource language code.
	 */
	public function setLang( ?string $lang ): void {
		if ( !$lang ) {
			return;
		}
		$this->lang = $lang;
		if ( $lang === 'mul' || $this->lang === 'www' || $this->lang === '' ) {
			$this->domainName = 'wikisource.org';
			$this->lang = '';
		} elseif ( $this->lang === 'beta' ) {
			$this->domainName = 'en.wikisource.beta.wmflabs.org';
			$this->lang = '';
		} elseif ( $this->lang === 'nan' ) {
			// Hardcoded override for the only Wikisource with a subdomain that doesn't match its language code.
			$this->domainName = 'zh-min-nan.wikisource.org';
			$this->lang = 'zh-min-nan';
		} else {
			$this->domainName = $this->lang . '.wikisource.org';
		}
	}

	/**
	 * @return string the domain name of the wiki being used
	 */
	public function getDomainName(): string {
		return $this->domainName;
	}

	public function getLang(): string {
		return $this->lang;
	}

	/**
	 * Get the localized namespace names of the current Wikisource. Cached for one month.
	 * @return string[]
	 */
	public function getNamespaces(): array {
		if ( isset( $this->namespaces[ $this->getLang() ] ) ) {
			return $this->namespaces[ $this->getLang() ];
		}
		$cacheKey = Util::sanitizeCacheKey( 'namespaces_' . $this->getLang() );
		$this->namespaces[ $this->getLang() ] = $this->cache->get( $cacheKey, function ( CacheItemInterface $cacheItem ) {
			$this->logger->notice( 'Fetching namespace names for ' . $this->getLang() );
			$cacheItem->expiresAfter( new DateInterval( 'P1M' ) );
			$response = $this->queryAsync( [ 'meta' => 'siteinfo', 'siprop' => 'namespaces|namespacealiases' ] )
				->wait();

			$namespaces = [];
			foreach ( $response[ 'query' ][ 'namespaces' ] as $namespace ) {
				if ( array_key_exists( '*', $namespace ) && $namespace[ '*' ] ) {
					$namespaces[$namespace[ '*' ]] = $namespace[ 'id' ];
				}
				if ( array_key_exists( 'canonical', $namespace ) && $namespace[ 'canonical' ] ) {
					$namespaces[$namespace[ 'canonical' ] ] = $namespace[ 'id' ];
				}
			}
			foreach ( $response[ 'query' ][ 'namespacealiases' ] as $namespaceAlias ) {
				if ( array_key_exists( '*', $namespaceAlias ) ) {
					$namespaces[$namespaceAlias[ '*' ]] = $namespaceAlias[ 'id' ];
				}
			}
			return $namespaces;
		} );
		return $this->namespaces[ $this->getLang() ];
	}

	/**
	 * Get HTML of the 'about' page that will be appended to an exported ebook.
	 * @return string The HTML.
	 */
	public function getAboutPage(): string {
		$cacheKey = Util::sanitizeCacheKey( 'about_' . $this->getLang() );
		$content = $this->cache->get( $cacheKey, function ( CacheItemInterface $cacheItem ) {
			// Cache for 1 month.
			$cacheItem->expiresAfter( new DateInterval( 'P1M' ) );
			// Get the HTML from either this Wikisource or multilingual Wikisource.
			try {
				return $this->getPageAsync( 'MediaWiki:Wsexport_about' )->wait();
			} catch ( Exception $exception ) {
				$oldWikisourceApi = clone $this;
				$oldWikisourceApi->setLang( 'www' );
				return $oldWikisourceApi->getPageAsync( 'MediaWiki:Wsexport_about' )->wait();
			}
		} );
		$document = Util::buildDOMDocumentFromHtml( $content );
		$parser = new PageParser( $document );
		$document = $parser->getContent( true );
		// Add https to protocol-relative links.
		$aboutHtml = str_replace( 'href="//', 'href="https://', $document->saveXML() );
		// Fully qualify unqualified links.
		return str_replace( 'href="./', 'href="https://' . $this->getDomainName() . '/wiki/', $aboutHtml );
	}

	/**
	 * GET action
	 *
	 * @param string $url the target URL
	 * @param array $options
	 * @return PromiseInterface the body of the result
	 */
	public function getAsync( $url, array $options = [] ) {
		return $this->getClient()->requestAsync(
			'get',
			$url,
			$options
		)->then(
			static function ( ResponseInterface $response ) use ( $url ) {
				if ( $response->getStatusCode() !== 200 ) {
					throw new WsExportException( 'url-fetch-error', [ $url ], 500 );
				}
				return $response->getBody()->getContents();
			}
		);
	}

	/**
	 * @param string $url
	 * @param array $options
	 * @return PromiseInterface
	 */
	public function createAsyncRequest( string $url, array $options = [] ): PromiseInterface {
		return $this->getClient()->requestAsync(
			'get',
			$url,
			$options
		);
	}

	/**
	 * Disable caching.
	 */
	public function disableCache(): void {
		$this->cache = new NullAdapter();
	}

	/**
	 * API query
	 *
	 * @param array $params an associative array for params send to the api
	 * @return PromiseInterface a Promise with the result array
	 * @throws Exception
	 */
	public function queryAsync( $params ) {
		$params += [ 'action' => 'query', 'format' => 'json' ];
		return $this->getAsync(
			'https://' . $this->getDomainName() . '/w/api.php',
			[ 'query' => $params ]
		)->then(
			static function ( $result ) {
				$json = json_decode( $result, true );
				if ( isset( $json ) ) {
					return $json;
				} else {
					throw new Exception( 'invalid JSON: "' . $result . '": ' . json_last_error_msg() );
				}
			}
		);
	}

	/**
	 * api query. Give all pages of response
	 * @param array $params an associative array for params send to the api
	 * @return array an array with whe result of the api query
	 */
	public function completeQuery( $params ) {
		$data = [];
		$continue = true;
		do {
			$temp = $this->queryAsync( $params )->wait();
			$data = array_merge_recursive( $data, $temp );

			if ( array_key_exists( 'continue', $temp ) ) {
				foreach ( $temp['continue'] as $keys => $value ) {
					$params[$keys] = $value;
				}
			} else {
				$continue = false;
			}
		} while ( $continue );

		return $data;
	}

	/**
	 * @param string $title the title of the page
	 * @return PromiseInterface promise with the content of a page
	 */
	public function getPageAsync( $title ) {
		$url = 'https://' . $this->getDomainName() . '/api/rest_v1/page/html/' . urlencode( $title );
		return $this->getAsync( $url )
				->then(
					function ( string $result ) use ( $title ) {
						return Util::getXhtmlFromContent( $this->getLang(), $result, $title );
					},
					/** @return never */
					static function ( $reason ) use ( $title ) {
						throw new WsExportException( 'rest-page-not-found', [ $title ], 404, false );
					}
				);
	}

	/**
	 * @param string $url the url
	 * @return string the file content
	 */
	public function get( $url ) {
		return $this->getClient()->request( 'get', $url )->getBody()->getContents();
	}

	public function setClient( ClientInterface $client ): void {
		$this->client = $client;
	}

	/**
	 * @return ClientInterface
	 */
	public function getClient(): ClientInterface {
		if ( $this->client instanceof ClientInterface ) {
			return $this->client;
		}

		$handler = HandlerStack::create();

		// Logger.
		$handler->push( LoggingMiddleware::forLogger( $this->logger ), 'logging' );

		// Cache.
		if ( $this->cache instanceof NullAdapter ) {
			$cacheStrategy = new GreedyCacheStrategy( new Psr6CacheStorage( $this->cacheItemPool ), $this->cacheTtl );
			$handler->push( new CacheMiddleware( $cacheStrategy ), 'cache' );
		}

		$this->client = new Client( [
			'defaults' => [
				'connect_timeout' => self::CONNECT_TIMEOUT,
				'headers' => [ 'User-Agent' => self::USER_AGENT ],
				'timeout' => self::REQUEST_TIMEOUT
			],
			'handler' => $handler
		] );
		return $this->client;
	}

	/**
	 * Turn off logging.
	 */
	public function disableLogging(): void {
		$this->logger = new NullLogger();
	}
}
