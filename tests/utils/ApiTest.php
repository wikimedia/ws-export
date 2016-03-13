<?php

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Request;

class ApiTest extends \PHPUnit_Framework_TestCase
{
	public function testQueryAsyncParsesJsonResponse() {
		$api = $this->apiWithJsonResponse(['result' => 'test']);
		$result = $api->queryAsync(['prop' => 'revisions'])->wait();
		$this->assertEquals(['result' => 'test'], $result);
	}

	/**
	 * @expectedException Exception
	 * @expectedExceptionMessage invalid JSON: "xxx-invalid": Syntax error
	 */
	public function testQueryAsyncThrowsExceptionOnInvalidJsonResponse() {
		$api = $this->apiWithResponse(200, ['Content-Type' => 'application/json'], 'xxx-invalid');
		$result = $api->queryAsync([])->wait();
	}

	/**
	 * @expectedException HttpException
	 */
	public function testQueryAsyncRaisesExceptionOnHttpError() {
		$api = $this->apiWithResponse(404, [], 'Not found');
		$api->queryAsync(['prop' => 'revisions'])->wait();
	}

	private function apiWithJsonResponse($data) {
		return $this->apiWithResponse(200, ['Content-Type' => 'application/json'], json_encode($data));
	}

	private function apiWithResponse($status, $header, $body) {
		return new API('en', '', $this->mockClient([new Response($status, $header, $body)]));
	}

	private function mockClient($responses) {
		return new Client(['handler' => HandlerStack::create(new MockHandler($responses))]);
	}
}
