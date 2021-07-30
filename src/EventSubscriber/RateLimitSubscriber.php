<?php
declare( strict_types=1 );

namespace App\EventSubscriber;

use DateInterval;
use Krinkle\Intuition\Intuition;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

class RateLimitSubscriber implements EventSubscriberInterface {

	/** @var Intuition */
	protected $intuition;

	/** @var CacheItemPoolInterface */
	protected $cache;

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
		int $rateLimit,
		int $rateDuration
	) {
		$this->intuition = $intuition;
		$this->cache = $cache;
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

		// when a controller class defines multiple action methods, the controller
		// is returned as [$controllerInstance, 'methodName']
		if ( is_array( $controller ) ) {
			[ , $action ] = $controller;
		}

		// Abort if rate limitations are disabled or we're not exporting a book.
		if ( $this->rateLimit + $this->rateDuration === 0 || $action !== 'home' || !$request->get( 'page' ) ) {
			return;
		}

		$xff = $request->headers->get( 'x-forwarded-for', '' );
		if ( $xff === '' ) {
			// Happens in local environments, or outside of Cloud Services.
			return;
		}

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

	/**
	 * Throw exception for denied access due to spider crawl or hitting usage limits.
	 * @throws TooManyRequestsHttpException
	 */
	private function denyAccess() {
		// @phan-suppress-previous-line PhanPluginNeverReturnMethod
		$message = $this->intuition->msg( 'exceeded-rate-limitation', [
			'variables' => [ $this->rateDuration ]
		] );

		throw new TooManyRequestsHttpException( $this->rateDuration * 60, $message );
	}
}
