<?php

class ToolLoggerTest extends \PHPUnit_Framework_TestCase {
	public function testLoggerImplementsPsrLoggerInterface() {
		$logger = ToolLogger::get( 'test' );
		$this->assertInstanceOf( 'Psr\Log\LoggerInterface', $logger );
	}

	public function testReturnsNullLoggerIfDebuggingIsDisabled() {
		$logger = ToolLogger::get( 'test' );
		$this->assertCount( 1, $logger->getHandlers() );
		$this->assertInstanceOf( 'monolog\Handler\NullHandler', $logger->getHandlers()[0] );
	}

	public function testReturnsStderrLoggerIfDebuggingIsEnabled() {
		$this->enableDebug();
		$logger = ToolLogger::get( 'test' );
		$this->assertCount( 1, $logger->getHandlers() );
		$this->assertInstanceOf( 'monolog\Handler\StreamHandler', $logger->getHandlers()[0] );
	}

	public function testLoggerHasCorrectName() {
		$this->enableDebug();
		$logger = ToolLogger::get( 'test' );
		$this->assertEquals( 'test', $logger->getName() );
	}

	private function enableDebug() {
		global $wsexportConfig;
		$wsexportConfig = [ 'debug' => true ];
	}
}
