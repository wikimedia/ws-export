<?php

namespace App\Util\Semaphore;

/**
 * A currently acquired semaphore
 */
interface SemaphoreHandle {
	/** Releases the semaphore handle: allows another process to lock it. */
	public function release(): void;
}
