<?php

require_once __DIR__ . '/../test_init.php';

/**
 * @covers BookCreator
 */
class BookCreatorIntegrationTest extends \PHPUnit\Framework\TestCase {
	private $epubCheckJar = null;
	private $testResult = null;

	public function run( PHPUnit_Framework_TestResult $result = null ) {
		$this->epubCheckJar = $this->epubCheckJar();
		$this->testResult = $result;
		parent::run( $result );
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
		$command = 'java -jar ' . escapeshellarg( $this->epubCheckJar ) . ' --quiet --json ' .
			escapeshellarg( $jsonOut ) . ' ' . escapeshellarg( $file ) . ' 2>&1';

		exec( $command, $output, $exitCode );

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
		return array_filter( array_map( $mapper, $data ), function ( $location ) { return $location != null;
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
		exec( 'java -version >/dev/null 2>&1', $output, $exitCode );
		return $exitCode == 0;
	}
}

class Location implements PHPUnit_Framework_SelfDescribing {
	private $path;
	private $line = 0;
	private $column = 0;

	public function __construct( $path, $line, $column ) {
		$this->path = $path;
		$this->line = $line;
		$this->column = $column;
	}

	public function toString() {
		return '/' . $this->path . ':' . $this->line . ':' . $this->column;
	}
}

class EpubCheckResult implements PHPUnit_Framework_SelfDescribing {
	private $locations = [];
	private $additionalLocations;
	private $severity;
	private $message;

	/**
	 * @param string $severity
	 * @param string $message
	 * @param Location[] $locations
	 * @param int $additionalLocations
	 */
	public function __construct( $severity, $message, array $locations, $additionalLocations ) {
		$this->message = $message;
		$this->severity = $severity;
		$this->locations = $locations;
		$this->additionalLocations = $additionalLocations;
	}

	public function toString() {
		$allLocations = "\n\n\t" . implode( "\n\t", array_map( function ( Location $l ) {
			return $l->toString();
	 }, $this->locations ) );
		if ( $this->additionalLocations > 0 ) {
			$allLocations .= "\n\t + " . $this->additionalLocations . ' other locations';
		}
		return $this->message . $allLocations;
	}

	public function report( $test, PHPUnit_Framework_TestResult $listener ) {
		switch ( $this->severity ) {
			case "ERROR":
				$this->reportAsError( $test, $listener );
				break;
			case "WARNING":
				$this->reportAsWarning( $test, $listener );
				break;
		}
	}

	public function reportAsError( $test, PHPUnit_Framework_TestResult $listener ) {
		$listener->addError( $test, new PHPUnit_Framework_AssertionFailedError( $this->toString() ), 0 );
	}

	public function reportAsWarning( $test, PHPUnit_Framework_TestResult $listener ) {
		if ( method_exists( $listener, 'addWarning' ) ) { // TODO: remove when we will drop PHP 5.5 support
			$listener->addWarning( $test, new PHPUnit_Framework_Warning( $this->toString() ), 0 );
		}
	}
}
