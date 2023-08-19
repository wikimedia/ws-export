<?php

namespace App\Tests\Util;

use App\Util\Util;
use PHPUnit\Framework\TestCase;

class UtilTest extends TestCase {

	public function provideEncodeStringCases() {
		return [
			[ 'test_Δôü', '_test_dou' ],
			[ 'foo',      '_foo' ],
			[ 'æ,þ,η,ŋ',  '_ae_th_eh_ng' ],
			[ '.-!:?$',   '_._____' ],
			[ '🎉',       '__' ],
			[ 'Fóø Båř',  '_Foo_Bar' ],
		];
	}

	/**
	 * encodeString prefixes encoded strings with an incrementing counter
	 * which we omit from the tests by using assertStringEndsWith().
	 *
	 * @covers ::encodeString
	 * @dataProvider provideEncodeStringCases
	 */
	public function testEncodeString( $input, $expected ) {
		$this->assertStringEndsWith( $expected, Util::encodeString( $input ) );
	}
}
