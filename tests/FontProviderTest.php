<?php

namespace App\Tests;

use App\FontProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

class FontProviderTest extends TestCase {

	/** @var FontProvider */
	private $fontProvider;

	protected function setUp(): void {
		parent::setUp();
		$this->fontProvider = new FontProvider( new ArrayAdapter() );
	}

	/**
	 * @covers FontProvider::resolveName()
	 * @dataProvider provideResolveName
	 */
	public function testResolveName( $in, $out ) {
		$this->assertSame( $out, $this->fontProvider->resolveName( $in ) );
	}

	public function provideResolveName() {
		return [
			[ 'freeserif', 'FreeSerif' ],
			[ 'dejavu-sans', 'DejaVu Sans' ],
			[ 'linuxlibertine', 'Linux Libertine O' ],
		];
	}

	/**
	 * @covers FontProvider::getOne()
	 */
	public function testGetOne() {
		// Non-existing font.
		$this->assertSame( null, $this->fontProvider->getOne( 'Foo' ) );
		// Common font with four variants.
		$this->assertCount( 4, $this->fontProvider->getOne( 'FreeSerif' )['styles'] );
		// Font with one variant.
		$this->assertCount( 1, $this->fontProvider->getOne( 'Gubbi' )['styles'] );
	}

	/**
	 * @covers FontProvider::getCss()
	 */
	public function testGetCss() {
		$this->assertSame( '@font-face {  font-family: "FreeSerif";  font-weight: normal;  font-style: normal;  src: url("fonts/FreeSerif.ttf");}
@font-face {  font-family: "FreeSerif";  font-weight: 800;  font-style: normal;  src: url("fonts/FreeSerifBold.ttf");}
@font-face {  font-family: "FreeSerif";  font-weight: normal;  font-style: italic;  src: url("fonts/FreeSerifItalic.ttf");}
@font-face {  font-family: "FreeSerif";  font-weight: 800;  font-style: italic;  src: url("fonts/FreeSerifBoldItalic.ttf");}
body { font-family: "FreeSerif" }
', $this->fontProvider->getCss( 'FreeSerif' ) );
		$this->assertSame( '@font-face {  font-family: "Linux Libertine O";  font-weight: normal;  font-style: normal;  src: url("fonts/LinLibertine_R.otf");}
@font-face {  font-family: "Linux Libertine O";  font-weight: bold;  font-style: normal;  src: url("fonts/LinLibertine_RZ.otf");}
@font-face {  font-family: "Linux Libertine O";  font-weight: 800;  font-style: normal;  src: url("fonts/LinLibertine_RB.otf");}
@font-face {  font-family: "Linux Libertine O";  font-weight: normal;  font-style: italic;  src: url("fonts/LinLibertine_RI.otf");}
@font-face {  font-family: "Linux Libertine O";  font-weight: bold;  font-style: italic;  src: url("fonts/LinLibertine_RZI.otf");}
@font-face {  font-family: "Linux Libertine O";  font-weight: 800;  font-style: italic;  src: url("fonts/LinLibertine_RBI.otf");}
body { font-family: "Linux Libertine O" }
', $this->fontProvider->getCss( 'linuxlibertine' ) );

		$this->assertSame( '@font-face {  font-family: "Gubbi";  font-weight: normal;  font-style: normal;  src: url("fonts/Gubbi.ttf");}
body { font-family: "Gubbi" }
', $this->fontProvider->getCss( 'Gubbi' ) );

		$this->assertSame( '', $this->fontProvider->getCss( 'invalid-font-name' ) );
	}

	/**
	 * @covers FontProvider::getForLang()
	 */
	public function testGetForLang() {
		$this->assertNotEmpty( $this->fontProvider->getForLang( 'en' ) );
		$this->assertNotEmpty( $this->fontProvider->getForLang( 'kn' ) );
		$this->assertEmpty( $this->fontProvider->getForLang( 'not-a-lang' ) );
	}
}
