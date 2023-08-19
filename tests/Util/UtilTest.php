<?php

namespace App\Tests\Util;

use App\Util\Util;
use PHPUnit\Framework\TestCase;

class UtilTest extends TestCase {
	/**
	 * encodeString prefixes encoded strings with an incrementing counter
	 * which we omit from the tests by using assertStringEndsWith().
	 *
	 * @covers ::encodeString
	 */
	public function testEncodeString() {
		$this->assertStringEndsWith( '_test_dou', Util::encodeString( 'test_Δôü' ) );
		$this->assertStringEndsWith( '_foo', Util::encodeString( 'foo' ) );
		$this->assertStringEndsWith( '_._____', Util::encodeString( '.-!:?$' ) );
		$this->assertStringEndsWith( '__', Util::encodeString( '🎉' ) );
	}
}
