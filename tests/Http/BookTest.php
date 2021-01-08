<?php

namespace App\Tests\Http;

use App\Entity\GeneratedBook;
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
		$client->request( 'GET', '/book.php', [ 'page' => $title, 'lang' => $language ] );
		$headers = $client->getResponse()->headers;
		$this->assertSame( 'File Transfer', $headers->get( 'Content-Description' ) );
		$this->assertSame( 'application/epub+zip', $headers->get( 'Content-Type' ) );
		$this->assertSame( 'attachment; filename=The_Kiss_and_its_History.epub', $headers->get( 'Content-Disposition' ) );
		$this->assertSame( 200, $client->getResponse()->getStatusCode() );

		// Test that it took at least a second to generate.
		/** @var GeneratedBook $genBook */
		$genBook = self::$container
			->get( 'doctrine' )
			->getManager()
			->getRepository( GeneratedBook::class )
			->findOneBy( [ 'lang' => $language, 'title' => $title ], [ 'time' => 'DESC' ] );
		$this->assertGreaterThanOrEqual( 1, $genBook->getDuration() );
	}

	public function testGetNonExistingTitleDisplaysError() {
		$client = static::createClient();
		$client->request( 'GET', '/book.php', [ 'page' => 'xxx' ] );
		$this->assertStringContainsString( 'Page not found', $client->getResponse()->getContent() );
		$this->assertSame( 404, $client->getResponse()->getStatusCode() );
	}

	public function testGetInvalidFormatDisplaysError() {
		$client = static::createClient();
		$client->request( 'GET', '/book.php', [ 'page' => 'xxx', 'format' => 'xxx' ] );
		$this->assertStringContainsString( '&quot;xxx&quot; is not a valid format.', $client->getResponse()->getContent() );
		$this->assertSame( 404, $client->getResponse()->getStatusCode() );
	}

	/**
	 * @dataProvider provideGetLang()
	 */
	public function testGetLang( $query, $accept, $lang ) {
		$client = static::createClient();
		$client->request( 'GET', '/', [ 'lang' => $query ], [], [ 'HTTP_ACCEPT_LANGUAGE' => $accept ] );
		$this->assertStringContainsString( '<input name="lang" id="lang" type="text" size="3" maxlength="20" required="required"
					value="' . $lang . '" class="form-control input-mini"/>', $client->getResponse()->getContent() );
	}

	public function provideGetLang() {
		return [
			[ '', 'fr-CH, fr;q=0.9, en;q=0.8, de;q=0.7, *;q=0.5', 'fr' ],
			[ '', 'en-US', 'en' ],
			[ '', 'qq, en-fr', 'qq' ],
			[ 'bn', 'en-AU', 'bn' ],
		];
	}

	public function testTitlePrefill() {
		$client = static::createClient();
		$client->request( 'GET', '/', [ 'title' => 'A title' ] );
		$this->assertStringContainsString( '<input name="page" id="page" type="text" size="30" required="required" class="form-control"
					value="A&#x20;title" />', $client->getResponse()->getContent() );
	}
}
