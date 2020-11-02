<?php

namespace App\Tests\Http;

use App\CreationLog;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @covers CreationLog
 */
class StatTest extends WebTestCase {

	public function testGet() {
		$client = static::createClient();

		/** @var CreationLog $creationLog */
		$creationLog = self::$container->get( CreationLog::class );
		$creationLog->createTable();

		$client->request( 'GET', '/stat.php' );
		$this->assertStringContainsString( 'Stats for ', $client->getResponse()->getContent() );
	}
}
