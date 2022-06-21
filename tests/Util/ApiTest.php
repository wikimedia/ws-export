<?php

namespace App\Tests\Util;

use App\Util\Api;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\Cache\Adapter\NullAdapter;

/**
 * @covers \App\Util\Api
 */
class ApiTest extends TestCase {
	public function testQueryAsyncParsesJsonResponse() {
		$api = $this->apiWithJsonResponse( [ 'result' => 'test' ] );
		$result = $api->queryAsync( [ 'prop' => 'revisions' ] )->wait();
		$this->assertEquals( [ 'result' => 'test' ], $result );
	}

	public function testQueryAsyncThrowsExceptionOnInvalidJsonResponse() {
		$this->expectException( Exception::class );
		$this->expectExceptionMessage( 'invalid JSON: "xxx-invalid": Syntax error' );

		$api = $this->apiWithResponse( 200, [ 'Content-Type' => 'application/json' ], 'xxx-invalid' );
		$api->queryAsync( [] )->wait();
	}

	public function testQueryAsyncRaisesExceptionOnHttpError() {
		$this->expectException( ClientException::class );
		$api = $this->apiWithResponse( 404, [], 'Not found' );
		$api->queryAsync( [ 'prop' => 'revisions' ] )->wait();
	}

	private function apiWithJsonResponse( $data ) {
		return $this->apiWithResponse( 200, [ 'Content-Type' => 'application/json' ], json_encode( $data ) );
	}

	private function apiWithResponse( $status, $header, $body ) {
		$api = new Api( new NullLogger(), new NullAdapter(), new NullAdapter(), 60 );
		$api->setClient( $this->mockClient( [ new Response( $status, $header, $body ) ] ) );
		$api->setLang( 'en' );
		return $api;
	}

	private function mockClient( $responses ) {
		return new Client( [ 'handler' => HandlerStack::create( new MockHandler( $responses ) ) ] );
	}

	public function testGetAboutPage(): void {
		$api = $this->apiWithResponse( 200, [], '<html><body><a href="./Foo">Foo</a></body></html>' );
		$this->assertStringContainsString( '<body><a href="https://en.wikisource.org/wiki/Foo">Foo</a></body>', $api->getAboutPage() );
	}
}
