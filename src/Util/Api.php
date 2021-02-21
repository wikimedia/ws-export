<?php

namespace App\Util;

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
use Symfony\Component\Cache\Adapter\NullAdapter;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
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

	/** @var CacheItemPoolInterface */
	private $cache;

	/** @var string[][] */
	private $namespaces = [];

	public function __construct( LoggerInterface $logger, CacheItemPoolInterface $cacheItemPool, CacheInterface $cache, ClientInterface $client = null ) {
		$this->logger = $logger;
		$this->cache = $cache;
		if ( $client === null ) {
			$client = $this->createClient( $logger, $cacheItemPool );
		}
		$this->client = $client;
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
		} elseif ( $this->lang == 'wl' || $this->lang == 'wikilivres' ) {
			$this->domainName = 'wikilivres.org';
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
		$this->namespaces[ $this->getLang() ] = $this->cache->get( 'namespaces_' . $this->getLang(), function ( CacheItemInterface $cacheItem ) {
			$this->logger->notice( 'Fetching namespace names for ' . $this->getLang() );
			$cacheItem->expiresAfter( new DateInterval( 'P1M' ) );
			$response = $this->queryAsync( [ 'meta' => 'siteinfo', 'siprop' => 'namespaces|namespacealiases' ] )
				->wait();
			$namespaces = [];
			foreach ( $response[ 'query' ][ 'namespaces' ] as $namespace ) {
				if ( array_key_exists( '*', $namespace ) && $namespace[ '*' ] ) {
					$namespaces[] = $namespace[ '*' ];
				}
				if ( array_key_exists( 'canonical', $namespace ) && $namespace[ 'canonical' ] ) {
					$namespaces[] = $namespace[ 'canonical' ];
				}
			}
			foreach ( $response[ 'query' ][ 'namespacealiases' ] as $namespaceAlias ) {
				if ( array_key_exists( '*', $namespaceAlias ) ) {
					$namespaces[] = $namespaceAlias[ '*' ];
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
		$content = $this->cache->get( 'about_' . $this->getLang(), function ( CacheItemInterface $cacheItem ) {
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
	 * @return ClientInterface
	 */
	public function getClient() {
		return $this->client;
	}

	/**
	 * GET action
	 *
	 * @param string $url the target URL
	 * @param array $options
	 * @return PromiseInterface the body of the result
	 */
	public function getAsync( $url, array $options = [] ) {
		return $this->client->getAsync(
			$url,
			$options
		)->then(
			function ( ResponseInterface $response ) {
				if ( $response->getStatusCode() !== 200 ) {
					throw new HttpException( $response->getStatusCode() );
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
		return $this->client->getAsync(
			$url,
			$options
		);
	}

	/**
	 * API query
	 *
	 * @deprecated Use Api::queryAsync
	 *
	 * @param array $params parameters sent to the api
	 * @return array result of the api query
	 * @throws HttpException
	 */
	public function query( $params ) {
		return $this->queryAsync( $params )->wait();
	}

	/**
	 * Disable caching.
	 */
	public function disableCache(): void {
		// Disable Symfony cache.
		$this->cache = new NullAdapter();

		// Disable cache for Guzzle.
		/** @var HandlerStack */
		$stack = $this->client->getConfig( 'handler' );
		$stack->remove( 'cache' );
	}

	/**
	 * Get the cache. This is a temporary method and can be removed once Util::getTempFile() has been removed.
	 */
	public function getCache(): CacheItemPoolInterface {
		return $this->cache;
	}

	/**
	 * API query
	 *
	 * @param array $params an associative array for params send to the api
	 * @return PromiseInterface a Promise with the result array
	 * @throws HttpException
	 */
	public function queryAsync( $params ) {
		$params += [ 'action' => 'query', 'format' => 'json' ];
		return $this->getAsync(
			'https://' . $this->getDomainName() . '/w/api.php',
			[ 'query' => $params ]
		)->then(
			function ( $result ) {
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
	 * @throws HttpException
	 */
	public function completeQuery( $params ) {
		$data = [];
		$continue = true;
		do {
			$temp = $this->query( $params );
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
					function ( $reason ) use ( $title ) {
						throw new NotFoundHttpException( "Page not found for: $title" );
					}
				);
	}

	/**
	 * @param string $url the url
	 * @return string the file content
	 */
	public function get( $url ) {
		return $this->client->get( $url )->getBody()->getContents();
	}

	/**
	 * @param LoggerInterface $logger
	 * @return ClientInterface
	 */
	private function createClient( LoggerInterface $logger, CacheItemPoolInterface $cache ): ClientInterface {
		$handler = HandlerStack::create();

		// Logger.
		$handler->push( LoggingMiddleware::forLogger( $logger ), 'logging' );

		// Cache.
		$ttl = 12 * 60 * 60;
		$cacheStrategy = new GreedyCacheStrategy( new Psr6CacheStorage( $cache ), $ttl );
		$handler->push( new CacheMiddleware( $cacheStrategy ), 'cache' );

		return new Client( [
			'defaults' => [
				'connect_timeout' => self::CONNECT_TIMEOUT,
				'headers' => [ 'User-Agent' => self::USER_AGENT ],
				'timeout' => self::REQUEST_TIMEOUT
			],
			'handler' => $handler
		] );
	}
}
