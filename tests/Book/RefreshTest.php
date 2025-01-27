<?php

namespace App\Tests\Book;

use App\Book;
use App\FileCache;
use App\FontProvider;
use App\Generator\EpubGenerator;
use App\Refresh;
use App\Util\Api;
use App\Util\OnWikiConfig;
use Krinkle\Intuition\Intuition;
use Psr\Log\NullLogger;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

/**
 * @covers \App\Refresh
 */
class RefreshTest extends KernelTestCase {

	public function testRefresh() {
		self::bootKernel();

		$cache = new ArrayAdapter();
		$api = new Api( new NullLogger(), $cache, $cache, 0 );
		$api->setLang( 'en' );
		$refresh = new Refresh( $api, $cache );
		$intuition = new Intuition();

		// Test that the cache is initially empty.
		$this->assertFalse( $cache->hasItem( 'css_en' ) );

		// Export a book, and test that this fills the cache.
		$fileCache = self::getContainer()->get( FileCache::class );
		$epubGenerator = new EpubGenerator( new FontProvider( $cache, new OnWikiConfig( $api, $cache, $intuition ) ), $api, $intuition, $cache, $fileCache );
		$book = new Book();
		$book->lang = 'en';
		$book->title = 'Emma';
		$book->options = [ 'images' => false, 'fonts' => null, 'credits' => false ];
		$epubGenerator->create( $book );
		$this->assertTrue( $cache->hasItem( 'css_en' ) );

		// Then refresh, and check again that the cache is empty.
		$refresh->refresh();
		$this->assertFalse( $cache->hasItem( 'css_en' ) );
	}
}
