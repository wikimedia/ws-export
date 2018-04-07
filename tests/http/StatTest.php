<?php

require_once __DIR__ . '/../test_init.php';

/**
 * @covers CreationLog
 */
class StatTest extends \PHPUnit\Framework\TestCase {
	/**
	 * @runInSeparateProcess
	 */
	public function testGet() {
		$this->expectOutputRegex( '/' . preg_quote( 'Stats for ' ) . '/' );
		include __DIR__ . '/../../http/stat.php';
	}
}
