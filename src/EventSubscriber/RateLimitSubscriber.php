<?php
declare( strict_types=1 );

namespace App\EventSubscriber;

use DateInterval;
use Krinkle\Intuition\Intuition;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class RateLimitSubscriber implements EventSubscriberInterface {

	/** @var int Number of minutes to cache IP blocks. */
	private const BLOCK_CACHE_DURATION = 5;

	/** Constants for different types of reasons to deny exporting of books. */
	private const DENY_TYPE_RATE_LIMITING = 1;
	private const DENY_TYPE_BLOCKED = 2;

	/** @var Intuition */
	protected $intuition;

	/** @var CacheItemPoolInterface */
	protected $cache;

	/** @var HttpClientInterface */
	protected $client;

	/** @var int */
	protected $rateLimit;

	/** @var int */
	protected $rateDuration;

	/**
	 * @param Intuition $intuition
	 * @param CacheItemPoolInterface $cache
	 * @param int $rateLimit
	 * @param int $rateDuration
	 */
	public function __construct(
		Intuition $intuition,
		CacheItemPoolInterface $cache,
		HttpClientInterface $client,
		int $rateLimit,
		int $rateDuration
	) {
		$this->intuition = $intuition;
		$this->cache = $cache;
		$this->client = $client;
		$this->rateLimit = $rateLimit;
		$this->rateDuration = $rateDuration;
	}

	/**
	 * Register our interest in the kernel.controller event.
	 * @return string[]
	 */
	public static function getSubscribedEvents(): array {
		return [
			KernelEvents::CONTROLLER => 'onKernelController',
		];
	}

	/**
	 * Check if the current user has exceeded the configured usage limitations.
	 * @param ControllerEvent $event
	 */
	public function onKernelController( ControllerEvent $event ): void {
		$controller = $event->getController();
		$action = null;
		$request = $event->getRequest();
		$loggedIn = $request->hasSession() && $request->getSession()->has( 'logged_in_user' );

		// when a controller class defines multiple action methods, the controller
		// is returned as [$controllerInstance, 'methodName']
		if ( is_array( $controller ) ) {
			[ , $action ] = $controller;
		}

		// Abort if rate limitations are disabled or we're not exporting a book.
		if ( $loggedIn || $this->rateLimit + $this->rateDuration === 0 ||
			$action !== 'home' || !$request->query->get( 'page' )
		) {
			return;
		}

		$xff = $request->headers->get( 'x-forwarded-for' );
		if ( !$xff ) {
			// Happens in local environments, or outside of Cloud Services.
			return;
		}

		// First check Meta's global blocklist. This will ween out most bots.
		$this->checkMetaBlocklist( $xff );

		$cacheKey = "ratelimit.session." . md5( $xff );
		$cacheItem = $this->cache->getItem( $cacheKey );

		// If increment value already in cache, or start with 1.
		$count = $cacheItem->isHit() ? (int)$cacheItem->get() + 1 : 1;

		// Check if limit has been exceeded, and if so, throw an error.
		if ( $count > $this->rateLimit ) {
			$this->denyAccess();
		}

		// Reset the clock on every request.
		$cacheItem->set( $count )
			->expiresAfter( new DateInterval( 'PT' . $this->rateDuration . 'M' ) );
		$this->cache->save( $cacheItem );
	}

	private function checkMetaBlocklist( string $xff ): void {
		$cacheKey = "ratelimit.ipblock.$xff";
		$cacheItem = $this->cache->getItem( $cacheKey );

		if ( $cacheItem->isHit() ) {
			$this->denyAccess( self::DENY_TYPE_BLOCKED );
		}

		$response = json_decode( $this->client->request(
			'GET',
			"https://meta.wikimedia.org/w/api.php?action=query&list=globalblocks&bgip=$xff&format=json&formatversion=2"
		)->getContent(), true );

		$block = $response['query']['globalblocks'][0] ?? null;
		if ( $block === null ) {
			// No block.
			return;
		}

		// Cache this block so if we're rapidly hit by the same IP,
		// we don't unnecessarily re-query the API.
		$cacheItem->set( true )
			->expiresAfter( new DateInterval( 'PT' . self::BLOCK_CACHE_DURATION . 'M' ) );
		$this->cache->save( $cacheItem );

		// Throw 403 with a friendly message.
		$this->denyAccess( self::DENY_TYPE_BLOCKED );
	}

	/**
	 * Throw exception for denied access due to spider crawl or hitting usage limits.
	 * @param int $denyType One of the self::DENY_TYPE constants.
	 * @throws TooManyRequestsHttpException
	 * @throws AccessDeniedHttpException
	 */
	private function denyAccess( int $denyType = self::DENY_TYPE_RATE_LIMITING ) {
		// @phan-suppress-previous-line PhanPluginNeverReturnMethod
		if ( $denyType === self::DENY_TYPE_RATE_LIMITING ) {
			$message = $this->intuition->msg( 'exceeded-rate-limitation', [
				'variables' => [ $this->rateDuration ],
			] );
			throw new TooManyRequestsHttpException( $this->rateDuration * 60, $message );
		}

		// More information shown for 403s in error.html.twig.
		throw new AccessDeniedHttpException();
	}
}
