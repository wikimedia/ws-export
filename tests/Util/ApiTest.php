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
		$api = new Api( $this->mockClient( [ new Response( $status, $header, $body ) ] ) );
		$api->setLang( 'en' );
		return $api;
	}

	private function mockClient( $responses ) {
		return new Client( [ 'handler' => HandlerStack::create( new MockHandler( $responses ) ) ] );
	}
}
