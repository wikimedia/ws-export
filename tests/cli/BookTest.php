<?php

require_once __DIR__ . '/../../cli/book.php';

class BookTest extends \PHPUnit_Framework_TestCase
{
	/** @expectedException WSExport_InvalidArgumentException */
	public function testParseArguments() {
		// not sure how to test getopt(), since it relies on global state,
		// just test error case for now
		parseCommandLine();
	}

	/**
	 * @expectedException WSExport_InvalidArgumentException
	 * @expectedExceptionMessage The file format 'unknown' is unknown.
	 */
	public function testGetUnknownGeneratorRaisesException() {
		getGenerator( "unknown" );
	}

	public function testGetGeneratorEpub2() {
		$generator = getGenerator( 'epub-2' );
		$this->assertInstanceOf( 'Epub2Generator', $generator );
	}

	public function testGetGeneratorEpub3() {
		$generator = getGenerator( 'epub-3' );
		$this->assertInstanceOf( 'Epub3Generator', $generator );
	}

	public function testGetGeneratorMobi() {
		$generator = getGenerator( 'mobi' );
		$this->assertInstanceOf( 'ConvertGenerator', $generator );
		$this->assertEquals( 'application/x-mobipocket-ebook', $generator->getMimeType() );
	}

	public function testGetGeneratorTxt() {
		$generator = getGenerator( 'txt' );
		$this->assertInstanceOf( 'ConvertGenerator', $generator );
		$this->assertEquals( 'text/plain', $generator->getMimeType() );
	}

	public function testGetGeneratorRtf() {
		$generator = getGenerator( 'rtf' );
		$this->assertInstanceOf( 'ConvertGenerator', $generator );
		$this->assertEquals( 'application/rtf', $generator->getMimeType() );
	}

	public function testGetGeneratorPdfA4() {
		$generator = getGenerator( 'pdf-a4' );
		$this->assertInstanceOf( 'ConvertGenerator', $generator );
		$this->assertEquals( 'application/pdf', $generator->getMimeType() );
	}

	public function testGetGeneratorPdfA5() {
		$generator = getGenerator( 'pdf-a5' );
		$this->assertInstanceOf( 'ConvertGenerator', $generator );
		$this->assertEquals( 'application/pdf', $generator->getMimeType() );
	}

	public function testGetGeneratorPdfLetter() {
		$generator = getGenerator( 'pdf-letter' );
		$this->assertInstanceOf( 'ConvertGenerator', $generator );
		$this->assertEquals( 'application/pdf', $generator->getMimeType() );
	}
}
