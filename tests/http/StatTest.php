<?php

namespace App\Tests;

use App\CreationLog;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @covers CreationLog
 */
class StatTest extends WebTestCase {

	public function testGet() {
		$client = static::createClient();

		/** @var Connection $db */
		$db = self::$container->get( 'doctrine.dbal.default_connection' );
		( new CreationLog( $db ) )->createTable();

		$client->request( 'GET', '/stat.php' );
		$this->assertStringContainsString( 'Stats for ', $client->getResponse()->getContent() );
	}
}
