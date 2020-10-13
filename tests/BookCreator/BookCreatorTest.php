<?php

namespace App\Tests\BookCreator;

use App\Book;
use App\BookCreator;
use App\FontProvider;
use App\Util\Api;
use GuzzleHttp\Client;
use GuzzleHttp\Promise\Promise;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\Cache\Adapter\NullAdapter;

class BookCreatorTest extends TestCase {

	/**
	 * @covers \App\BookCreator::create()
	 */
	public function testCreate() {
		$mockApi = $this->createMock( Api::class );
		$promise = $this->createMock( Promise::class );
		$promise->method( 'wait' )->willReturn( 'Page content' );
		$mockApi->method( 'getPageAsync' )
			->with( 'Test_title' )
			->willReturn( $promise );
		$mockApi->method( 'query' )
			->willReturn( [ 'query' => [ 'pages' => [] ] ] );
		$mockApi->method( 'getClient' )
			->willReturn( new Client() );
		$mockApi->lang = 'fr';

		$cache = new NullAdapter();
		$logger = new NullLogger();
		$bookCreator = new BookCreator( $mockApi, $logger, new FontProvider( $cache ) );
		$bookCreator->setIncludeCredits( false );

		$this->assertEquals( 'fr', $bookCreator->getLang() );

		$bookCreator->setTitle( 'Test title' );
		$bookCreator->create();
	}
}
