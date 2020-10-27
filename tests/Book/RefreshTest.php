<?php

namespace App\Tests\Book;

use App\Refresh;
use App\Util\Api;
use App\Util\Util;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

/**
 * @covers Refresh
 */
class RefreshTest extends TestCase {

	public function testRefreshUpdatesI18N() {
		$this->refresh( 'en' );

		$i18n = unserialize( Util::getTempFile( 'en', 'i18n.sphp' ) );
		$this->assertIsArray( $i18n );
		$this->assertEquals( 'Test-Title', $i18n[ 'title_page' ] );
	}

	public function testRefreshUpdatesEpubCssWikisource() {
		$this->refresh( 'en' );

		$css = Util::getTempFile( 'en', 'epub.css' );
		$this->assertStringEndsWith( '#TEST-CSS', $css );
	}

	public function testRefreshUpdatesAboutXhtmlWikisource() {
		$this->refresh( 'en' );

		$about = Util::getTempFile( 'en', 'about.xhtml' );
		$this->assertStringContainsString( 'Test-About-Content', $about );
	}

	public function testRefreshUpdatesNamespacesList() {
		$this->refresh( 'en' );

		$namespaces = unserialize( Util::getTempFile( 'en', 'namespaces.sphp' ) );
		$this->assertEquals( [ '0' => 'test' ], $namespaces );
	}

	private function refresh( $lang ) {
		$api = new Api( $this->mockClient( $this->defaultResponses() ) );
		$api->setLang( $lang );
		$refresh = new Refresh( $api );
		$refresh->refresh();
	}

	private function mockClient( $responses ) {
		return new Client( [ 'handler' => HandlerStack::create( new MockHandler( $responses ) ) ] );
	}

	private function defaultResponses() {
		return [
			$this->mockI18NResponse( 'title_page = "Test-Title"' ),
			$this->mockCssWikisourceResponse( '#TEST-CSS' ),
			$this->mockAboutWikisourceResponse( 'Test-About-Title', 'Test-About-Content' ),
			$this->mockNamespacesListResponse( [ '*' => 'test' ] )
		];
	}

	private function mockI18NResponse( $content ) {
		return new Response( 200, [ 'Content' => 'text/x-wiki' ], $content );
	}

	private function mockCssWikisourceResponse( $content ) {
		return new Response( 200, [ 'Content' => 'text/css' ], $content );
	}

	private function mockAboutWikisourceResponse( $title, $content ) {
		return new Response( 200, [ 'Content' => 'application/json' ], json_encode( [
			'query' => [
				'pages' => [
					[
						'title' => $title,
						'revisions' => [ [ '*' => $content ] ]
					],
				]
			]
		] ) );
	}

	private function mockNamespacesListResponse( $namespaces ) {
		return new Response( 200, [ 'Content' => 'application/json' ],
			json_encode( [ 'query' => [ 'namespaces' => [ $namespaces ], 'namespacealiases' => [] ] ] )
		 );
	}
}
