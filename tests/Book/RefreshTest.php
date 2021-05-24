<?php

namespace App\Tests\Book;

use App\Book;
use App\FontProvider;
use App\Generator\EpubGenerator;
use App\Refresh;
use App\Util\Api;
use App\Util\OnWikiConfig;
use GuzzleHttp\Client;
use Krinkle\Intuition\Intuition;
use Psr\Log\NullLogger;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

/**
 * @covers Refresh
 */
class RefreshTest extends KernelTestCase {

	public function testRefresh() {
		$cache = new ArrayAdapter();
		$api = new Api( new NullLogger(), $cache, $cache, new Client(), 0 );
		$api->setLang( 'en' );
		$refresh = new Refresh( $api, $cache );
		$intuition = new Intuition();

		// Test that the cache is initially empty.
		$this->assertFalse( $cache->hasItem( 'css_en' ) );

		// Export a book, and test that this fills the cache.
		$epubGenerator = new EpubGenerator( new FontProvider( $cache, new OnWikiConfig( $api, $cache, $intuition ) ), $api, $intuition, $cache );
		$book = new Book();
		$book->lang = 'en';
		$book->title = 'Emma';
		$book->options = [ 'images' => false, 'fonts' => false, 'credits' => false ];
		$epubGenerator->create( $book );
		$this->assertTrue( $cache->hasItem( 'css_en' ) );

		// Then refresh, and check again that the cache is empty.
		$refresh->refresh();
		$this->assertFalse( $cache->hasItem( 'css_en' ) );
	}
}
