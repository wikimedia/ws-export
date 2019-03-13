<?php

require_once __DIR__ . '/../test_init.php';

/**
 * @covers ToolLogger
 */
class ToolLoggerTest extends \PHPUnit\Framework\TestCase {
	public function testLoggerImplementsPsrLoggerInterface() {
		$logger = ToolLogger::get( 'test' );
		$this->assertInstanceOf( \Psr\Log\LoggerInterface::class, $logger );
	}

	public function testReturnsNullLoggerIfDebuggingIsDisabled() {
		$logger = ToolLogger::get( 'test' );
		$this->assertCount( 1, $logger->getHandlers() );
		$this->assertInstanceOf( \monolog\Handler\NullHandler::class, $logger->getHandlers()[0] );
	}

	public function testReturnsStderrLoggerIfDebuggingIsEnabled() {
		$this->enableDebug();
		$logger = ToolLogger::get( 'test' );
		$this->assertCount( 1, $logger->getHandlers() );
		$this->assertInstanceOf( \monolog\Handler\StreamHandler::class, $logger->getHandlers()[0] );
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
