<?php

namespace App\Tests\Book;

use App\FileCache;
use App\FontProvider;
use App\Generator\ConvertGenerator;
use App\Generator\EpubGenerator;
use App\GeneratorSelector;
use App\Util\Api;
use App\Util\OnWikiConfig;
use Exception;
use Krinkle\Intuition\Intuition;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

/**
 * @covers GeneratorSelector
 */
class GeneratorSelectorTest extends KernelTestCase {

	/** @var FontProvider */
	private $fontProvider;

	/** @var Api */
	private $api;

	/** @var GeneratorSelector */
	private $generatorSelector;

	/** @var Intuition */
	private $intuition;

	public function setUp(): void {
		parent::setUp();
		self::bootKernel();
		$cache = new ArrayAdapter();
		$this->fontProvider = new FontProvider( $cache, self::$container->get( OnWikiConfig::class ) );
		$this->api = self::$container->get( Api::class );
		$this->intuition = self::$container->get( Intuition::class );
		$fileCache = self::$container->get( FileCache::class );
		$epubGenerator = new EpubGenerator( $this->fontProvider, $this->api, $this->intuition, $cache, $fileCache );
		$convertGenerator = new ConvertGenerator( 10, $fileCache, $epubGenerator );
		$this->generatorSelector = new GeneratorSelector( $this->fontProvider, $this->api, $convertGenerator, $this->intuition, $cache, $fileCache );
	}

	public function testGetUnknownGeneratorRaisesException() {
		$this->expectException( Exception::class );
		$this->expectExceptionMessage( "The file format 'unknown' is unknown." );
		$this->generatorSelector->getGenerator( "unknown" );
	}

	public function testGetGeneratorEpub() {
		$generator = $this->generatorSelector->getGenerator( 'epub' );
		$this->assertInstanceOf( EpubGenerator::class, $generator );
	}

	public function testGetGeneratorEpub3() {
		$generator = $this->generatorSelector->getGenerator( 'epub-3' );
		$this->assertInstanceOf( EpubGenerator::class, $generator );
	}

	public function testGetGeneratorEpub2() {
		$generator = $this->generatorSelector->getGenerator( 'epub-2' );
		$this->assertInstanceOf( ConvertGenerator::class, $generator );
		$this->assertEquals( 'application/epub+zip', $generator->getMimeType() );
	}

	public function testGetGeneratorMobi() {
		$generator = $this->generatorSelector->getGenerator( 'mobi' );
		$this->assertInstanceOf( ConvertGenerator::class, $generator );
		$this->assertEquals( 'application/x-mobipocket-ebook', $generator->getMimeType() );
	}

	public function testGetGeneratorTxt() {
		$generator = $this->generatorSelector->getGenerator( 'txt' );
		$this->assertInstanceOf( ConvertGenerator::class, $generator );
		$this->assertEquals( 'text/plain', $generator->getMimeType() );
	}

	public function testGetGeneratorRtf() {
		$generator = $this->generatorSelector->getGenerator( 'rtf' );
		$this->assertInstanceOf( ConvertGenerator::class, $generator );
		$this->assertEquals( 'application/rtf', $generator->getMimeType() );
	}

	public function testGetGeneratorOdt() {
		$generator = $this->generatorSelector->getGenerator( 'odt' );
		$this->assertInstanceOf( ConvertGenerator::class, $generator );
		$this->assertEquals( 'application/rtf', $generator->getMimeType() );
	}

	public function testGetGeneratorPdfA4() {
		$generator = $this->generatorSelector->getGenerator( 'pdf-a4' );
		$this->assertInstanceOf( ConvertGenerator::class, $generator );
		$this->assertEquals( 'application/pdf', $generator->getMimeType() );
	}

	public function testGetGeneratorPdfA5() {
		$generator = $this->generatorSelector->getGenerator( 'pdf-a5' );
		$this->assertInstanceOf( ConvertGenerator::class, $generator );
		$this->assertEquals( 'application/pdf', $generator->getMimeType() );
	}

	public function testGetGeneratorPdfLetter() {
		$generator = $this->generatorSelector->getGenerator( 'pdf-letter' );
		$this->assertInstanceOf( ConvertGenerator::class, $generator );
		$this->assertEquals( 'application/pdf', $generator->getMimeType() );
	}
}
