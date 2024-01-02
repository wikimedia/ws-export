<?php

namespace App\Util\Semaphore;

/**
 * A semaphore aka a lock allowing multiple threads/processes to acquire it.
 */
interface Semaphore {
	/**
	 * Attempts to lock the semaphore, returns null if it can't be locked because the semaphore is full.
	 */
	public function tryLock(): ?SemaphoreHandle;
}
