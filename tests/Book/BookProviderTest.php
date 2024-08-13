<?php

namespace App\Tests\Book;

use App\BookProvider;
use App\FileCache;
use App\Repository\CreditRepository;
use App\Util\Api;
use DOMDocument;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\Cache\Adapter\NullAdapter;

/**
 * @covers BookProvider
 */
class BookProviderTest extends TestCase {
	private BookProvider $bookProvider;
	private $mockHandler;

	public function setUp(): void {
		$responses = [
			// Namespaces.
			new Response( 200, [], json_encode( [ 'query' => [ 'namespaces' => [ [ 'id' => 5, '*' => 'test' ] ], 'namespacealiases' => [] ] ] ) ),
			// Image responses for testImageUrls()
			new Response( 404, [ 'Content-Type' => 'image/png' ], '' ),
			new Response( 404, [ 'Content-Type' => 'image/png' ], '' ),
			// The rest of these responses are required for mocking the Refresh process ('about' page, etc.).
			new Response( 200, [], '' ),
			new Response( 404, [], '' ), // mock returning 404 in first api call in Refresh::getAboutXhtmlWikisource
			new Response( 200, [], '' ), // mock getting content from '$oldWikisourceApi' in Refresh::getAboutXhtmlWikisource
		];
		$this->mockHandler = new MockHandler( $responses );
		$api = new Api( new NullLogger(), new NullAdapter(), new NullAdapter(), 0 );
		$api->setClient( new Client( [ 'handler' => HandlerStack::create( $this->mockHandler ) ] ) );
		$api->setLang( 'en' );
		$creditRepository = $this->getMockBuilder( CreditRepository::class )->disableOriginalConstructor()->getMock();
		$fileCache = new FileCache( dirname( __DIR__, 2 ) );
		$this->bookProvider = new BookProvider( $api, [ 'categories' => false, 'credits' => true ], $creditRepository, $fileCache );
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
		$this->assertEquals( [], array_keys( $book->credits ) );
		$this->assertSame( '', $book->type );
		$this->assertSame( '', $book->translator );
		$this->assertSame( '', $book->illustrator );
		$this->assertEquals( [], $book->categories );
		$this->assertEquals( [], $book->pictures );
		$this->assertEquals( [], $book->credits );
	}

	public function testGetCreditsReturnsUsersSortedByEditCountAndsBotsLast() {
		$book = $this->bookProvider->getMetadata( 'test', false, new DOMDocument() );
		$this->assertEquals( [], array_keys( $book->credits ) );
	}

	private function parseDocument( $filename ) {
		$doc = new DOMDocument();
		$this->assertTrue( $doc->loadHTMLFile( $filename ), 'parsing of "' . $filename . '"" failed' );
		return $doc;
	}

	public function testTitleEntities() {
		$doc = new DOMDocument();
		$doc->loadHTML( '<body><p class="ws-title">Title &amp; name</p></body>' );
		$book = $this->bookProvider->getMetadata( 'Title name', false, $doc );
		$this->assertSame( 'Title name', $book->title );
		$this->assertSame( 'Title & name', $book->name );
	}

	/**
	 * @dataProvider provideImageUrls
	 */
	public function testImageUrls( string $html, string $url ) {
		$doc = new DOMDocument();
		$doc->loadHTML( "<body>$html</body>" );
		$book = $this->bookProvider->getMetadata( 'Title name', false, $doc );
		$this->assertSame( $url, reset( $book->pictures )->url );
	}

	public function provideImageUrls() {
		return [
			[ '<img src="//upload.wikimedia.org/foo.jpg" />', 'https://upload.wikimedia.org/foo.jpg' ],
			[ '<img src="/w/extensions/ExampleExt/foo.jpg" />', 'https://en.wikisource.org/w/extensions/ExampleExt/foo.jpg' ],
		];
	}
}
