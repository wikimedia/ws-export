<?php

namespace App\Tests;

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
		include __DIR__ . '/../../public/stat.php';
	}
}
