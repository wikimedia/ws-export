<?php

namespace App\Tests\Util\Semaphore;

use App\Util\Semaphore\SemaphoreHandle;
use App\Util\Semaphore\UnixSemaphore;
use PHPUnit\Framework\TestCase;

class UnixSemaphoreTest extends TestCase {

	/**
	 * @covers \App\Util\Semaphore\UnixSemaphore
	 * @covers \App\Util\Semaphore\UnixSemaphoreHandle
	 */
	public function testUnixSemaphore() {
		$semaphore = new UnixSemaphore( 1, 1 );
		$handle = $semaphore->tryLock();
		$this->assertInstanceOf( SemaphoreHandle::class, $handle );
		$this->assertNull( $semaphore->tryLock() );
		$handle->release();
		$this->assertInstanceOf( SemaphoreHandle::class, $semaphore->tryLock() );
	}
}
