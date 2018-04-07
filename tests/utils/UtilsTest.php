<?php

require_once __DIR__ . '/../../utils/utils.php';

class UtilsTest extends \PHPUnit\Framework\TestCase {
	/**
	 * @covers ::encodeString
	 */
	public function testEncodeString() {
		 $this->assertEquals( 'c0_test_dou', encodeString( 'test_Δôü' ) );
	}
}
