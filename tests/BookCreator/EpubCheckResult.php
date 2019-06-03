<?php

namespace App\Tests\BookCreator;

use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\SelfDescribing;
use PHPUnit\Framework\TestResult;
use PHPUnit\Framework\Warning;

class EpubCheckResult implements SelfDescribing {
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

	public function toString(): string {
		$allLocations = "\n\n\t" . implode( "\n\t", array_map( function ( Location $l ) {
				return $l->toString();
		}, $this->locations ) );
		if ( $this->additionalLocations > 0 ) {
			$allLocations .= "\n\t + " . $this->additionalLocations . ' other locations';
		}
		return $this->message . $allLocations;
	}

	public function report( $test, TestResult $listener ) {
		switch ( $this->severity ) {
			case "ERROR":
				$this->reportAsError( $test, $listener );
				break;
			case "WARNING":
				$this->reportAsWarning( $test, $listener );
				break;
		}
	}

	public function reportAsError( $test, TestResult $listener ) {
		$listener->addError( $test, new AssertionFailedError( $this->toString() ), 0 );
	}

	public function reportAsWarning( $test, TestResult $listener ) {
		if ( method_exists( $listener, 'addWarning' ) ) { // TODO: remove when we will drop PHP 5.5 support
			$listener->addWarning( $test, new Warning( $this->toString() ), 0 );
		}
	}
}
