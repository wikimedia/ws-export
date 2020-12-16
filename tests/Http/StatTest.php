<?php

namespace App\Tests\Http;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @covers GeneratedBook
 */
class StatTest extends WebTestCase {

	public function testGet() {
		$client = static::createClient();
		$client->request( 'GET', '/stat.php' );
		$this->assertStringContainsString( 'Stats for ', $client->getResponse()->getContent() );
	}
}
