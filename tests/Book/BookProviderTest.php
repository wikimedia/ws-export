<?php

namespace App\Tests\Book;

use App\BookProvider;
use App\Util\Api;
use DOMDocument;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

/**
 * @covers BookProvider
 */
class BookProviderTest extends TestCase {
	private $bookProvider;
	private $mockHandler;

	public function setUp(): void {
		$creditResponse = [
			'User B' => [ 'count' => 1, 'flags' => [ 'editor', 'reviewer' ] ],
			'Bot User' => [ 'count' => 5, 'flags' => [ 'bot' ] ],
			'User A' => [ 'count' => 20, 'flags' => [ 'autoreview', 'editor', 'reviewer', 'sysop' ] ]
		];
		$responses = [
			new Response( 200, [ 'Content-Type' => 'application/json' ], json_encode( $creditResponse ) ),
			// The rest of these responses are required for mocking the Refresh process (namespaces, 'about' page, etc.)
			new Response( 200, [], '' ),
			new Response( 200, [], '' ),
			new Response( 200, [], '' ),
			new Response( 200, [], json_encode( [ 'query' => [ 'namespaces' => [ [ '*' => 'test' ] ], 'namespacealiases' => [] ] ] ) ),
		];
		$this->mockHandler = new MockHandler( $responses );
		$client = new Client( [ 'handler' => HandlerStack::create( $this->mockHandler ) ] );
		$api = new Api( new NullLogger(), $client );
		$api->setLang( 'en' );
		$this->bookProvider = new BookProvider( $api, [ 'categories' => false, 'credits' => true ] );
	}

	public function testGetMetadata() {
		$document = $this->parseDocument( __DIR__ . '/fixtures/Tales_of_Unrest/Navigation.html' );
		$book = $this->bookProvider->getMetadata( 'test', true, $document );

		$this->assertEquals( 'test', $book->title );
		$this->assertEquals( 'en', $book->lang );
		$this->assertEquals( 'Joseph Conrad', $book->author );
		$this->assertSame( '', $book->year );
		$this->assertSame( '', $book->publisher );
		$this->assertSame( '', $book->volume );
		$this->assertSame( '', $book->scan );
		$this->assertSame( '', $book->cover );
		$this->assertSame( '', $book->type );
		$this->assertSame( '', $book->translator );
		$this->assertSame( '', $book->illustrator );
		$this->assertEquals( [], $book->categories );
		$this->assertEquals( [], $book->pictures );
		$this->assertEquals( [], $book->credits );
	}

	public function testGetCreditsReturnsUsersSortedByEditCountAndsBotsLast() {
		$book = $this->bookProvider->getMetadata( 'test', false, new DOMDocument() );
		// sorted by edit count, bots last
		$this->assertEquals( [ 'User A', 'User B', 'Bot User' ], array_keys( $book->credits ) );
	}

	public function testGetCreditsUsesCorrectToolServerURL() {
		$this->bookProvider->getMetadata( 'test', false, new DOMDocument() );

		$this->assertEquals(
			'https://tools.wmflabs.org/phetools/credits.py?lang=en&format=json&page=test',
			$this->mockHandler->getLastRequest()->getUri()->__toString() );
	}

	private function parseDocument( $filename ) {
		$doc = new DOMDocument();
		$this->assertTrue( $doc->loadHTMLFile( $filename ), 'parsing of "' . $filename . '"" failed' );
		return $doc;
	}
}
