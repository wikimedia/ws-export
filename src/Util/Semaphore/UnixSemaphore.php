<?php

namespace App\Util\Semaphore;

use Exception;
use SysvSemaphore;

/**
 * A semaphore backed by POSIX file APIs.
 * Does not work on Windows.
 */
class UnixSemaphore implements Semaphore {
	/** @var int */
	private $semaphoreKey;
	/** @var int */
	private $capacity;
	/** @var SysvSemaphore|null the lazily initialized semaphore descriptor */
	private $semaphore;

	/**
	 * @param int $semaphoreKey unique identifier for this semaphore. Should be shared by all the processes using it.
	 * @param int $capacity how many processes can lock the same semaphore.
	 */
	public function __construct( int $semaphoreKey, int $capacity ) {
		$this->semaphoreKey = $semaphoreKey;
		$this->capacity = $capacity;
		$this->semaphore = null;
	}

	public function tryLock(): ?SemaphoreHandle {
		if ( $this->semaphore === null ) {
			$semaphore = sem_get( $this->semaphoreKey, $this->capacity );
			if ( $semaphore === false ) {
				throw new Exception( "Failed to create semaphore key $this->semaphoreKey" );
			}
			$this->semaphore = $semaphore;
		}

		if ( !sem_acquire( $this->semaphore, true ) ) {
			return null; // Semaphore already full
		}

		// We return the handle
		return new UnixSemaphoreHandle( $this->semaphore );
	}
}
