<?php

namespace App\Tests\BookCreator;

use App\BookCreator;
use App\EpubCheck\EpubCheck;
use App\FileCache;
use App\FontProvider;
use App\Generator\ConvertGenerator;
use App\Generator\EpubGenerator;
use App\GeneratorSelector;
use App\Repository\CreditRepository;
use App\Util\Api;
use App\Util\OnWikiConfig;
use Krinkle\Intuition\Intuition;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

/**
 * @covers \App\BookCreator
 * @group integration
 */
class BookCreatorIntegrationTest extends KernelTestCase {

	/** @var FontProvider */
	private $fontProvider;

	/** @var Api */
	private $api;

	/** @var GeneratorSelector */
	private $generatorSelector;

	/** @var CreditRepository */
	private $creditRepository;

	/** @var EpubCheck */
	private $epubCheck;

	/** @var Intuition */
	private $intuition;

	/** @var FileCache */
	private $fileCache;

	public function setUp(): void {
		self::bootKernel();
		$cache = new ArrayAdapter();
		$this->fontProvider = new FontProvider( $cache, self::getContainer()->get( OnWikiConfig::class ) );
		$this->api = self::getContainer()->get( Api::class );
		$this->intuition = self::getContainer()->get( Intuition::class );
		$this->fileCache = self::getContainer()->get( FileCache::class );
		$epubGenerator = new EpubGenerator( $this->fontProvider, $this->api, $this->intuition, $cache, $this->fileCache );
		$convertGenerator = new ConvertGenerator( 30, $this->fileCache, $epubGenerator );
		$this->generatorSelector = new GeneratorSelector( $this->fontProvider, $this->api, $convertGenerator, $this->intuition, $cache, $this->fileCache );
		$this->creditRepository = self::getContainer()->get( CreditRepository::class );
		$this->epubCheck = self::getContainer()->get( EpubCheck::class );
	}

	public function bookProvider() {
		return [
			[ 'Around_the_Moon', 'en' ],
			[ 'Fables_de_La_Fontaine/édition_1874/Le_Philosophe_Scythe', 'fr' ]
		 ];
	}

	/**
	 * @dataProvider bookProvider
	 * @group exclude-from-ci
	 */
	public function testCreateBookEpub2( $title, $language ) {
		$epubFile = $this->createBook( $title, $language, 'epub-2' );
		$this->checkEpub( $epubFile );
	}

	 /**
	  * @dataProvider bookProvider
	  */
	public function testCreateBookEpub3( $title, $language ) {
		$epubFile = $this->createBook( $title, $language, 'epub-3' );
		$this->checkEpub( $epubFile );
	}

	 /**
	  * @dataProvider bookProvider
	  */
	public function testCreateBookMobi( $title, $language ) {
		$this->createBook( $title, $language, 'mobi' );
	}

	private function createBook( $title, $language, $format ) {
		$this->api->setLang( $language );
		$creator = BookCreator::forApi( $this->api, $format, $this->generatorSelector, $this->creditRepository, $this->fileCache );
		$creator->create( $title, [ 'credits' => false ] );
		$this->assertFileExists( $creator->getFilePath() );
		$this->assertNotNull( $creator->getBook() );
		return $creator->getFilePath();
	}

	/**
	 * Check an epub and fail if there are any errors.
	 * @param string $file
	 */
	private function checkEpub( string $file ) {
		foreach ( $this->epubCheck->check( $file ) as $result ) {
			if ( $result->isError() ) {
				$msg = $result->getMessage();
				if ( $result->getLocations() ) {
					$msg .= ' -- Location 1 of ' . count( $result->getLocations() ) . ': ' . $result->getLocations()[0];
				}
				$this->fail( $msg );
			}
		}
	}
}
