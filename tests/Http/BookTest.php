<?php

namespace App\Tests\Http;

use App\CreationLog;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @covers BookCreator
 * @group integration
 */
class BookTest extends WebTestCase {

	public function bookProvider() {
		return [
			[ 'The_Kiss_and_its_History', 'en' ],
		];
	}

	public function testGetEmptyPage() {
		$client = static::createClient();
		$client->request( 'GET', '/book.php' );
		$contentTypeHeader = $client->getResponse()->headers->get( 'Content-Type' );
		$this->assertSame( 'text/html; charset=UTF-8', $contentTypeHeader );
		$this->assertStringContainsString( 'Export books from Wikisource in many different file formats.', $client->getResponse()->getContent() );
	}

	/**
	 * @dataProvider bookProvider
	 */
	public function testGetPage( $title, $language ) {
		$client = static::createClient();
		$creationLog = self::$container->get( CreationLog::class );
		$creationLog->createTable();

		$client->request( 'GET', '/book.php', [ 'page' => $title, 'lang' => $language ] );
		$headers = $client->getResponse()->headers;
		$this->assertSame( 'File Transfer', $headers->get( 'Content-Description' ) );
		$this->assertSame( 'application/epub+zip', $headers->get( 'Content-Type' ) );
		$this->assertSame( 'attachment; filename=The_Kiss_and_its_History.epub', $headers->get( 'Content-Disposition' ) );
		$this->assertSame( 200, $client->getResponse()->getStatusCode() );
	}

	public function testGetNonExistingTitleDisplaysError() {
		$client = static::createClient();
		$client->request( 'GET', '/book.php', [ 'page' => 'xxx' ] );
		$this->assertStringContainsString( 'Page revision not found', $client->getResponse()->getContent() );
		$this->assertSame( 404, $client->getResponse()->getStatusCode() );
	}

	public function testGetInvalidFormatDisplaysError() {
		$client = static::createClient();
		$client->request( 'GET', '/book.php', [ 'page' => 'xxx', 'format' => 'xxx' ] );
		$this->assertStringContainsString( "The file format 'xxx' is unknown.", $client->getResponse()->getContent() );
		$this->assertSame( 500, $client->getResponse()->getStatusCode() );
	}
}
