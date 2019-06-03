<?php

namespace App\Tests\Util;

use App\Util\ToolLogger;
use monolog\Handler\NullHandler;
use monolog\Handler\StreamHandler;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @covers ToolLogger
 */
class ToolLoggerTest extends TestCase {
	public function testLoggerImplementsPsrLoggerInterface() {
		$logger = ToolLogger::get( 'test' );
		$this->assertInstanceOf( LoggerInterface::class, $logger );
	}

	public function testReturnsNullLoggerIfDebuggingIsDisabled() {
		$logger = ToolLogger::get( 'test' );
		$this->assertCount( 1, $logger->getHandlers() );
		$this->assertInstanceOf( NullHandler::class, $logger->getHandlers()[0] );
	}

	public function testReturnsStderrLoggerIfDebuggingIsEnabled() {
		$this->enableDebug();
		$logger = ToolLogger::get( 'test' );
		$this->assertCount( 1, $logger->getHandlers() );
		$this->assertInstanceOf( StreamHandler::class, $logger->getHandlers()[0] );
	}

	public function testLoggerHasCorrectName() {
		$this->enableDebug();
		$logger = ToolLogger::get( 'test' );
		$this->assertEquals( 'test', $logger->getName() );
	}

	private function enableDebug() {
		global $wsexportConfig;
		$wsexportConfig['debug'] = true;
	}
}
