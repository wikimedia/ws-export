<?php

namespace App\Util\Semaphore;

use SysvSemaphore;

class UnixSemaphoreHandle implements SemaphoreHandle {
	/** @var resource|SysvSemaphore */
	private $semaphore;
	/** @var bool */
	private $isReleased;

	public function __construct( $semaphore ) {
		$this->semaphore = $semaphore;
		$this->isReleased = false;
	}

	public function release(): void {
		if ( $this->isReleased ) {
			return; // Already released
		}
		$this->isReleased = true;
		sem_release( $this->semaphore );
	}

	public function __destruct() {
		$this->release();
	}
}
