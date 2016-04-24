<?php

require_once __DIR__ . '/../../book/PageParser.php';

class PageParserTest extends PHPUnit_Framework_TestCase {
	private $pageParser;

	/** @before */
	public function before() {
		$this->pageParser = $this->parseFile( __DIR__ . '/fixtures/Tales_of_Unrest/Navigation.html' );
	}

	public function testGetChaptersList() {
		$chapterList = $this->pageParser->getChaptersList( 'Tales Of Unrest', [], [] );
		$this->assertCount( 14, $chapterList );
	}

	public function testGetFullChaptersList() {
		$chapterList = $this->pageParser->getFullChaptersList( 'Tales Of Unrest', [], [] );
		$this->assertCount( 14, $chapterList );
	}

	public function testPictureList() {
		$pictureList = $this->pageParser->getPicturesList();
		$this->assertCount( 2, $pictureList );
		$this->assertArrayHasKey( '18px-Wikimedia-logo.svg.png', $pictureList );
		$this->assertArrayHasKey( '48px-PD-icon.svg.png', $pictureList );
	}

	public function testGetPagesListEmpty() {
		$pagesList = $this->pageParser->getPagesList();
		$this->assertCount( 0, $pagesList );
	}

	public function testGetPagesList() {
		$parser = $this->parseFile( __DIR__ . '/fixtures/Pacotilha_poetica/Se_namora_por_gosto_ou_por_precisão.html' );
		$pagesList = $parser->getPagesList();
		$this->assertEquals( [
			'Página:Pacotilha_poetica.pdf/10',
			'Página:Pacotilha_poetica.pdf/11',
			'Página:Pacotilha_poetica.pdf/12',
			'Página:Pacotilha_poetica.pdf/13'
		], $pagesList );
	}

	public function testMetadataIsSetReturnsTrueIfSet() {
		$this->assertTrue( $this->pageParser->metadataIsSet( 'ws-author' ) );
	}

	public function testMetadataIsSetReturnsFalseIfNotSet() {
		$this->assertFalse( $this->pageParser->metadataIsSet( 'ws-xxx' ) );
	}

	public function testGetMetadataReturnsValueIfSet() {
		$data = $this->pageParser->getMetadata( 'ws-author' );
		$this->assertEquals( 'Joseph Conrad', $data );
	}

	public function testGetMetadataReturnsEmptyIfNotSet() {
		$data = $this->pageParser->getMetadata( 'ws-xxx' );
		$this->assertEmpty( $data );
	}

	public function testGetContentReturnsADOMDocument() {
		$content = $this->pageParser->getContent( true );
		$this->assertInstanceOf( 'DOMDocument', $content );
	}

	private function parseFile( $filename ) {
		$doc = new DOMDocument();
		$this->assertTrue( $doc->loadHTMLFile( $filename ), 'parsing of "' . $filename . '"" failed' );
		return new PageParser( $doc );
	}
}
