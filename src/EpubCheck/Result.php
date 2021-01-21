<?php

namespace App\EpubCheck;

class Result {

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

	public function isError(): bool {
		return $this->severity === 'ERROR';
	}

	public function getMessage(): string {
		return $this->message;
	}

	public function getLocations(): array {
		return $this->locations;
	}

	public function toString(): string {
		$allLocations = "\n\n\t" . implode( "\n\t", array_map( function ( Location $l ) {
				return $l->toString();
		}, $this->locations ) );
		if ( $this->additionalLocations > 0 ) {
			$allLocations .= "\n\t + " . $this->additionalLocations . ' other locations';
		}
		return $this->message . $allLocations;
	}
}
