<?php

namespace App\Tests\BookCreator;

use App\BookCreator;
use App\FontProvider;
use App\GeneratorSelector;
use App\Util\Api;
use PHPUnit\Framework\TestResult;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Process\Process;

/**
 * @covers BookCreator
 * @group integration
 */
class BookCreatorIntegrationTest extends KernelTestCase {
	private $epubCheckJar = null;
	private $testResult = null;

	/** @var FontProvider */
	private $fontProvider;

	/** @var Api */
	private $api;

	/** @var GeneratorSelector */
	private $generatorSelector;

	public function run( TestResult $result = null ): TestResult {
		$this->epubCheckJar = $this->epubCheckJar();
		$this->testResult = $result;
		return parent::run( $result );
	}

	public function setUp(): void {
		self::bootKernel();
		$this->fontProvider = new FontProvider( new ArrayAdapter() );
		$this->api = self::$container->get( Api::class );
		$this->generatorSelector = new GeneratorSelector( $this->fontProvider, $this->api );
	}

	public function bookProvider() {
		return [
			[ 'The_Kiss_and_its_History', 'en' ],
			[ 'Les_Fleurs_du_mal', 'fr' ]
		 ];
	}

	/**
	 * @dataProvider bookProvider
	 * @group exclude-from-ci
	 */
	public function testCreateBookEpub2( $title, $language ) {
		$epubFile = $this->createBook( $title, $language, 'epub-2' );
		$this->epubCheck( $epubFile );
	}

	 /**
	  * @dataProvider bookProvider
	  * @group exclude-from-ci
	  */
	 public function testCreateBookEpub3( $title, $language ) {
		 $epubFile = $this->createBook( $title, $language, 'epub-3' );
		 $this->epubCheck( $epubFile );
	 }

	 /**
	  * @dataProvider bookProvider
	  */
	 public function testCreateBookMobi( $title, $language ) {
		 $this->createBook( $title, $language, 'mobi' );
	 }

	private function createBook( $title, $language, $format ) {
		$this->api->setLang( $language );
		$creator = BookCreator::forApi( $this->api, $format, [ 'credits' => false ], $this->generatorSelector );
		$creator->create( $title );
		$this->assertFileExists( $creator->getFilePath() );
		$this->assertNotNull( $creator->getBook() );
		return $creator->getFilePath();
	}

	private function epubCheck( $file ) {
		if ( $this->epubCheckJar == null || getenv( 'SKIP_EPUBCHECK' ) ) {
			$this->markTestSkipped( 'EpubCheck not found. Please provide it uing the EPUBCHECK_JAR environment variable' );
		}
		$jsonOut = tempnam( sys_get_temp_dir(), 'results-' . $file . '.json' );
		$process = new Process( [ 'java', '-jar', $this->epubCheckJar, '--quiet', '--json', $jsonOut, $file ] );
		$process->run();

		/** @var EpubCheckResult $checkResult */
		foreach ( $this->parseResults( $jsonOut ) as $checkResult ) {
			$checkResult->reportAsWarning( $this, $this->testResult );
		}
	}

	private function parseResults( $file ) {
		$decoded = json_decode( file_get_contents( $file ), true );
		$this->assertNotNull( $decoded, json_last_error_msg() );
		return $this->mapResults( $decoded['messages'] );
	}

	private function mapLocations( $data ) {
		$mapper = function ( $location ) {
			$path = $location['path'];
			$line = $location['line'];
			$column = $location['column'];

			if ( $line != -1 ) {
				return new Location( $path, $line, $column );
			} else {
				return null;
			}
		};
		return array_filter( array_map( $mapper, $data ), function ( $location ) {
			return $location != null;
		} );
	}

	private function mapResults( $data ) {
		$mapper = function ( $message ) {
			$severity = $message['severity'];
			$message_text = $message['message'];
			$locations = $this->mapLocations( $message['locations'] );
			$additionalLocations = $message['additionalLocations'];
			return new EpubCheckResult( $severity, $message_text, $locations, $additionalLocations );
		};
		return array_map( $mapper, $data );
	}

	private function epubCheckJar() {
		$epubCheckJar = getenv( 'EPUBCHECK_JAR' );
		if ( $epubCheckJar && file_exists( $epubCheckJar ) && $this->isJavaInstalled() ) {
			return $epubCheckJar;
		} else {
			return null;
		}
	}

	private function isJavaInstalled() {
		$process = new Process( [ 'java', '-version' ] );
		$process->run();
		return $process->isSuccessful();
	}
}
