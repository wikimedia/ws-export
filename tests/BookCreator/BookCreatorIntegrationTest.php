<?php

namespace App\Tests\BookCreator;

use App\BookCreator;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\TestResult;
use Symfony\Component\Process\Process;

/**
 * @covers BookCreator
 * @group exclude-from-ci
 */
class BookCreatorIntegrationTest extends TestCase {
	private $epubCheckJar = null;
	private $testResult = null;

	public function run( TestResult $result = null ): TestResult {
		$this->epubCheckJar = $this->epubCheckJar();
		$this->testResult = $result;
		return parent::run( $result );
	}

	public function bookProvider() {
		return [
			[ 'The_Kiss_and_its_History', 'en' ],
			[ 'Les_Fleurs_du_mal', 'fr' ]
		 ];
	}

	/**
	 * @dataProvider bookProvider
	 * @group integration
	 * @group epub2
	 */
	public function testCreateBookEpub2( $title, $language ) {
		$epubFile = $this->createBook( $title, $language, 'epub-2' );
		$this->epubCheck( $epubFile );
	}

	 /**
	  * @dataProvider bookProvider
	  * @group integration
	  * @group epub3
	  */
	 public function testCreateBookEpub3( $title, $language ) {
		 $epubFile = $this->createBook( $title, $language, 'epub-3' );
		 $this->epubCheck( $epubFile );
	 }

	 /**
	  * @dataProvider bookProvider
	  * @group integration
	  * @group mobi
	  */
	 public function testCreateBookMobi( $title, $language ) {
		 $this->createBook( $title, $language, 'mobi' );
	 }

	private function createBook( $title, $language, $format ) {
		$creator = BookCreator::forLanguage( $language, $format, [ 'credits' => false ] );
		list( $book, $file ) = $creator->create( $title );
		$this->assertFileExists( $file );
		$this->assertNotNull( $book );
		return $file;
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
