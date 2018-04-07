<?php

require_once __DIR__ . '/../test_init.php';

/**
 * @covers GeneratorSelector
 */
class GeneratorSelectorTest extends PHPUnit\Framework\TestCase {
	/**
	 * @expectedException WSExportInvalidArgumentException
	 * @expectedExceptionMessage The file format 'unknown' is unknown.
	 */
	public function testGetUnknownGeneratorRaisesException() {
		GeneratorSelector::select( "unknown" );
	}

	public function testGetGeneratorEpub2() {
		$generator = GeneratorSelector::select( 'epub-2' );
		$this->assertInstanceOf( 'Epub2Generator', $generator );
	}

	public function testGetGeneratorEpub3() {
		$generator = GeneratorSelector::select( 'epub-3' );
		$this->assertInstanceOf( 'Epub3Generator', $generator );
	}

	public function testGetGeneratorMobi() {
		$generator = GeneratorSelector::select( 'mobi' );
		$this->assertInstanceOf( 'ConvertGenerator', $generator );
		$this->assertEquals( 'application/x-mobipocket-ebook', $generator->getMimeType() );
	}

	public function testGetGeneratorTxt() {
		$generator = GeneratorSelector::select( 'txt' );
		$this->assertInstanceOf( 'ConvertGenerator', $generator );
		$this->assertEquals( 'text/plain', $generator->getMimeType() );
	}

	public function testGetGeneratorRtf() {
		$generator = GeneratorSelector::select( 'rtf' );
		$this->assertInstanceOf( 'ConvertGenerator', $generator );
		$this->assertEquals( 'application/rtf', $generator->getMimeType() );
	}

	public function testGetGeneratorOdt() {
		$generator = GeneratorSelector::select( 'odt' );
		$this->assertInstanceOf( 'ConvertGenerator', $generator );
		$this->assertEquals( 'application/rtf', $generator->getMimeType() );
	}

	public function testGetGeneratorPdfA4() {
		$generator = GeneratorSelector::select( 'pdf-a4' );
		$this->assertInstanceOf( 'ConvertGenerator', $generator );
		$this->assertEquals( 'application/pdf', $generator->getMimeType() );
	}

	public function testGetGeneratorPdfA5() {
		$generator = GeneratorSelector::select( 'pdf-a5' );
		$this->assertInstanceOf( 'ConvertGenerator', $generator );
		$this->assertEquals( 'application/pdf', $generator->getMimeType() );
	}

	public function testGetGeneratorPdfLetter() {
		$generator = GeneratorSelector::select( 'pdf-letter' );
		$this->assertInstanceOf( 'ConvertGenerator', $generator );
		$this->assertEquals( 'application/pdf', $generator->getMimeType() );
	}
}
