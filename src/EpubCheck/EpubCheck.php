<?php

namespace App\EpubCheck;

use Symfony\Component\Process\Process;
use ZipArchive;

class EpubCheck {

	/** @var string */
	private $epubCheckPath;

	public function __construct( string $epubCheckPath ) {
		$this->epubCheckPath = $epubCheckPath;
	}

	/**
	 * @param string $filePath
	 * @return Result[]
	 */
	public function check( string $filePath ): array {
		$jsonFile = tempnam( sys_get_temp_dir(), 'results-' . $filePath . '.json' );
		$process = new Process( [ 'java', '-jar', $this->epubCheckPath, '--quiet', '--json', $jsonFile, $filePath ] );
		$process->run();
		$decoded = json_decode( file_get_contents( $jsonFile ), true );
		unlink( $jsonFile );
		$results = [];
		foreach ( $decoded['messages'] ?? [] as $message ) {
			$locations = $this->mapLocations( $filePath, $message['locations'] );
			$results[] = new Result( $message['severity'], $message['message'], $locations, $message['additionalLocations'] );
		}
		return $results;
	}

	/**
	 * @return Location[]
	 */
	private function mapLocations( string $epubPath, array $data ): array {
		$locations = [];
		foreach ( $data as $i => $location ) {
			$path = $location['path'];
			$line = $location['line'];
			$column = $location['column'];
			if ( $line === -1 ) {
				continue;
			}
			if ( $i === 0 ) {
				// Retrieve context source for the first location only, for quicker tests.
				$zip = new ZipArchive();
				$zip->open( $epubPath );
				$fileContents = $zip->getFromName( $path );
				$lines = explode( "\n", $fileContents );
				$contextLines = array_slice( $lines, $line - 2, 3, true );
			} else {
				$contextLines = [];
			}
			$locations[] = new Location( $path, $line, $column, $contextLines );
		}
		return $locations;
	}
}
