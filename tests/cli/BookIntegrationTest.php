<?php

require_once __DIR__ . '/../../cli/book.php';

class BookIntegrationTest extends \PHPUnit_Framework_TestCase {
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
		$output = createBook( $title, $language, $format, sys_get_temp_dir(), [ 'credits' => false ] );
		$this->assertFileExists( $output );
		return $output;
	}

	private function epubCheck( $file ) {
		if ( $this->epubCheckJar == null ) {
			return;
		}
		$jsonOut = tempnam( sys_get_temp_dir(), 'results-' . $file . '.json' );
		$expandedEpub = $this->expandEpub( $file );
		$command = 'java -jar ' . escapeshellarg( $this->epubCheckJar ) . ' --quiet --mode exp --json ' .
			escapeshellarg( $jsonOut ) . ' ' . escapeshellarg( $expandedEpub ) . ' 2>&1';

		exec( $command, $output, $exitCode );

		/** @var EpubCheckResult $checkResult */
		foreach ( $this->parseResults( $jsonOut, $expandedEpub ) as $checkResult ) {
			$checkResult->reportAsWarning( $this, $this->testResult );
		}
	}

	private function expandEpub( $file ) {
		$zip = new ZipArchive();
		$this->assertTrue( $zip->open( $file, ZipArchive::CHECKCONS ) );
		$expandedEpub = tempnam( sys_get_temp_dir(), 'unpacked-epub-' . $file );
		$this->assertTrue( unlink( $expandedEpub ) );
		$this->assertTrue( mkdir( $expandedEpub ) );
		$this->assertTrue( $zip->extractTo( $expandedEpub ) );
		$zip->close();

		return $expandedEpub;
	}

	private function parseResults( $file, $basePath ) {
		$decoded = json_decode( file_get_contents( $file ), true );
		$this->assertNotNull( $decoded, json_last_error_msg() );
		return $this->mapResults( $decoded['messages'], $basePath );
	}

	private function mapLocations( $data, $basePath ) {
		$mapper = function( $location ) use( $basePath ) {
			$path = $location['path'];
			$line = $location['line'];
			$column = $location['column'];

			if ( $line != -1 ) {
				return new Location( $basePath, $path, $line, $column );
			} else {
				return null;
			}
		};
		return array_filter( array_map( $mapper, $data ), function( $location ) { return $location != null;

	 } );
	}

	private function mapResults( $data, $basePath ) {
		$mapper = function( $message ) use( $basePath ) {
			$severity = $message['severity'];
			$message_text = $message['message'];
			$locations = $this->mapLocations( $message['locations'], $basePath );
			$additionalLocations = $message['additionalLocations'];
			return new EpubCheckResult( $severity, $message_text, $locations, $additionalLocations );
		};
		return array_map( $mapper, $data );
	}

	private function epubCheckJar() {
		$epubCheckJar =  getenv( 'EPUBCHECK_JAR' );
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
	private $base = null;
	private $path = null;
	private $line = 0;
	private $column = 0;

	public function __construct( $base, $path, $line, $column ) {
		$this->base = $base;
		$this->path = $path;
		$this->line = $line;
		$this->column = $column;
	}

	public function toString() {
		return $this->base . '/' . $this->path . ':' . $this->line . ':' . $this->column;
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
	function __construct( $severity, $message, array $locations, $additionalLocations ) {
		$this->message = $message;
		$this->severity = $severity;
		$this->locations = $locations;
		$this->additionalLocations = $additionalLocations;
	}

	public function toString() {
		$allLocations = "\n\n\t" . join( "\n\t", array_map( function( Location $l ) {
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
		if( method_exists( $listener, 'addWarning' ) ) { //TODO: remove when we will drop PHP 5.5 support
			$listener->addWarning( $test, new PHPUnit_Framework_Warning( $this->toString() ), 0 );
		}
	}
}
