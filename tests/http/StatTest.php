<?php

namespace App\Tests;

use App\CreationLog;
use PHPUnit\Framework\TestCase;

/**
 * @covers CreationLog
 */
class StatTest extends TestCase {
	/**
	 * @runInSeparateProcess
	 */
	public function testGet() {
		$this->expectOutputRegex( '/' . preg_quote( 'Stats for ' ) . '/' );
		$log = CreationLog::singleton();
		$log->createTable();
		include __DIR__ . '/../../public/stat.php';
		// Clean up.
		$log->getPdo()->exec( 'DROP TABLE IF EXISTS ' . $log->getTableName() );
	}
}
