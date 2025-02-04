<?php

namespace App\Tests;

use App\FontProvider;
use App\Util\Api;
use App\Util\OnWikiConfig;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use Krinkle\Intuition\Intuition;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\NullAdapter;

class FontProviderTest extends TestCase {

	/** @var FontProvider */
	private $fontProvider;

	protected function setUp(): void {
		parent::setUp();
		$api = new Api( new NullLogger(), new NullAdapter(), new NullAdapter(), 0 );
		$api->setClient( new Client( [ 'handler' => HandlerStack::create( new MockHandler( [] ) ) ] ) );
		$this->fontProvider = new FontProvider( new ArrayAdapter(), new OnWikiConfig( $api, new ArrayAdapter(), new Intuition() ) );
	}

	/**
	 * @covers \App\FontProvider::resolveName()
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
			[ 'opendyslexic', 'OpenDyslexic' ]
		];
	}

	/**
	 * @covers \App\FontProvider::getOne()
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
	 * @covers \App\FontProvider::getCss()
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

		$this->assertSame( '@font-face {  font-family: "OpenDyslexic";  font-weight: normal;  font-style: normal;  src: url("fonts/OpenDyslexic-Regular.woff");}
@font-face {  font-family: "OpenDyslexic";  font-weight: 800;  font-style: normal;  src: url("fonts/OpenDyslexic-Bold.woff");}
@font-face {  font-family: "OpenDyslexic";  font-weight: normal;  font-style: italic;  src: url("fonts/OpenDyslexic-Italic.woff");}
@font-face {  font-family: "OpenDyslexic";  font-weight: 800;  font-style: italic;  src: url("fonts/OpenDyslexic-BoldItalic.woff");}
body { font-family: "OpenDyslexic" }
',
		$this->fontProvider->getCss( 'OpenDyslexic' ) );

		$this->assertSame( '', $this->fontProvider->getCss( 'invalid-font-name' ) );
	}

	/**
	 * @covers \App\FontProvider::getForLang()
	 */
	public function testGetForLang() {
		$this->assertNotEmpty( $this->fontProvider->getForLang( 'en' ) );
		$this->assertNotEmpty( $this->fontProvider->getForLang( 'kn' ) );
		$this->assertSame( [], $this->fontProvider->getForLang( 'not-a-lang' ) );
	}
}
