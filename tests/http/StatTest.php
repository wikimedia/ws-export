<?php

require_once __DIR__ . '/../test_init.php';

class StatTest extends \PHPUnit_Framework_TestCase {
	/**
	 * @runInSeparateProcess
	 */
	public function testGet() {
		$this->expectOutputRegex( '/' . preg_quote( 'Stats for ' ) . '/' );
		include __DIR__ . '/../../http/stat.php';
	}
}
