<?php

namespace App\Tests\Book;

use App\Refresh;
use App\Util\Api;
use App\Util\Util;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Psr\Log\NullLogger;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Cache\Adapter\NullAdapter;

/**
 * @covers Refresh
 */
class RefreshTest extends KernelTestCase {

	/** @var Api */
	private $api;

	public function setUp(): void {
		parent::setUp();
		self::bootKernel();
		$this->api = self::$container->get( Api::class );
	}

	public function testRefreshUpdatesI18N() {
		$this->refresh( 'en' );

		$i18n = unserialize( Util::getTempFile( $this->api, 'en', 'i18n.sphp' ) );
		$this->assertIsArray( $i18n );
		$this->assertEquals( 'Test-Title', $i18n[ 'title_page' ] );
	}

	public function testRefreshUpdatesEpubCssWikisource() {
		$this->refresh( 'en' );

		$css = Util::getTempFile( $this->api, 'en', 'epub.css' );
		$this->assertStringEndsWith( '#TEST-CSS', $css );
	}

	public function testRefreshUpdatesAboutXhtmlWikisource() {
		$this->refresh( 'en' );

		$about = Util::getTempFile( $this->api, 'en', 'about.xhtml' );
		$this->assertStringContainsString( 'Test-About-Content', $about );
	}

	private function refresh( $lang ) {
		$api = new Api( new NullLogger(), new NullAdapter(), new NullAdapter(), $this->mockClient( $this->defaultResponses() ) );
		$api->setLang( $lang );
		$refresh = new Refresh( $api );
		$refresh->refresh();
	}

	private function mockClient( $responses ) {
		return new Client( [ 'handler' => HandlerStack::create( new MockHandler( $responses ) ) ] );
	}

	private function defaultResponses() {
		return [
			$this->mockI18NResponse( 'title_page = "Test-Title"' ),
			$this->mockCssWikisourceResponse( '#TEST-CSS' ),
			$this->mockAboutWikisourceResponse( 'Test-About-Title', 'Test-About-Content' ),
		];
	}

	private function mockI18NResponse( $content ) {
		return new Response( 200, [ 'Content' => 'text/x-wiki' ], $content );
	}

	private function mockCssWikisourceResponse( $content ) {
		return new Response( 200, [ 'Content' => 'text/css' ], $content );
	}

	private function mockAboutWikisourceResponse( $title, $content ) {
		return new Response( 200, [ 'Content' => 'application/json' ],
				'<!DOCTYPE html>
				<html prefix="dc: http://purl.org/dc/terms/ mw: http://mediawiki.org/rdf/" about="https://en.wikisource.org/wiki/Special:Redirect/revision/2952249">
				<head prefix="mwr: https://en.wikisource.org/wiki/Special:Redirect/"><meta property="mw:TimeUuid" content="27feca60-13e5-11eb-ae2c-cd9b7fbfbfd2"/>
				<meta charset="utf-8"/><meta property="mw:pageId" content="791503"/><meta property="mw:pageNamespace" content="0"/>
				<link rel="dc:replaces" resource="mwr:revision/2952206"/><meta property="mw:revisionSHA1" content="3b67a798e367dda2bebc6a7a6f272ffd7cd7bfcf"/>
				<meta property="dc:modified" content="2011-06-11T09:02:29.000Z"/><meta property="mw:html:version" content="2.1.0"/>
				<link rel="dc:isVersionOf" href="//en.wikisource.org/wiki/' . urlencode( $title ) . '"/><title>' . $title . '</title>
				<base href="//en.wikisource.org/wiki/"/><link rel="stylesheet" href="/w/load.php?lang=en&amp;modules=mediawiki.skinning.content.parsoid%7Cmediawiki.skinning.interface%7Csite.styles%7Cmediawiki.page.gallery.styles%7Cext.cite.style%7Cext.cite.styles&amp;only=styles&amp;skin=vector"/><meta http-equiv="content-language" content="en"/><meta http-equiv="vary" content="Accept"/></head>
				<body>' . $content . '</body></html>'
			);
	}
}
