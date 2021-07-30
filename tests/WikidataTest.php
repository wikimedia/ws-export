<?php

namespace App\Tests;

use App\Wikidata;
use GuzzleHttp\Client;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\Cache\Adapter\NullAdapter;

class WikidataTest extends TestCase {

	/** @var Wikidata */
	private $wikidata;

	protected function setUp(): void {
		parent::setUp();
		$client = new Client();
		$this->wikidata = new Wikidata( new NullAdapter(), new NullLogger(), $client );
	}

	/**
	 * @covers Wikidata::getWikisourceLangs()
	 */
	public function testLanguageList() {
		// Get the list in English and most are in their own language.
		$langs = $this->wikidata->getWikisourceLangs( 'en' );
		$this->assertSame( $langs['sv'], 'svenskspråkiga Wikisource' );
		$this->assertSame( $langs['en'], 'English Wikisource' );
		$this->assertSame( $langs['mul'], 'Multilingual Wikisource' );
		// Get the list in a different language and the only one changed should be mul.
		$langs2 = $this->wikidata->getWikisourceLangs( 'fr' );
		$this->assertSame( $langs2['en'], 'English Wikisource' );
		$this->assertSame( $langs2['sv'], 'svenskspråkiga Wikisource' );
		$this->assertSame( $langs2['mul'], 'Wikisource multilingue' );
		// Note that this test doesn't test the fallback to the interface language for missing local labels,
		// because this will hopefully be fixed on Wikidata and so wouldn't be repeatable.
	}
}
