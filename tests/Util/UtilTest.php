<?php

namespace App\Tests\Util;

use App\Util\Util;
use PHPUnit\Framework\TestCase;

class UtilTest extends TestCase {
	/**
	 * @covers ::encodeString
	 */
	public function testEncodeString() {
		 $this->assertStringEndsWith( '_test_dou', Util::encodeString( 'test_Δôü' ) );
	}
}
