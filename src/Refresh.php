<?php

namespace App;

use App\Util\Api;
use Psr\Cache\CacheItemPoolInterface;

class Refresh {

	/** @var Api */
	protected $api;

	/** @var CacheItemPoolInterface */
	private $cacheItemPool;

	public function __construct( Api $api, CacheItemPoolInterface $cacheItemPool ) {
		$this->api = $api;
		$this->cacheItemPool = $cacheItemPool;
	}

	public function refresh() {
		if ( !is_dir( $this->getTempFileName( '' ) ) ) {
			mkdir( $this->getTempFileName( '' ) );
		}

		$this->cacheItemPool->deleteItems( [
			'namespaces_' . $this->api->getLang(),
			'about_' . $this->api->getLang(),
			'css_' . $this->api->getLang(),
		] );
	}

	protected function getTempFileName( $name ) {
		$cache = FileCache::singleton();

		return $cache->getDirectory() . '/' . $this->api->getLang() . '/' . $name;
	}
}
