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
	 * @covers \App\Wikidata::getWikisourceLangs()
	 */
	public function testLanguageList() {
		// Get the list in English and most are in their own language.
		$langs = $this->wikidata->getWikisourceLangs( 'en' );
		$this->assertSame( 'svenskspråkiga Wikisource', $langs['sv'] );
		$this->assertSame( 'English Wikisource', $langs['en'] );
		$this->assertSame( 'Multilingual Wikisource', $langs['mul'] );
		// Get the list in a different language and the only one changed should be mul.
		$langs2 = $this->wikidata->getWikisourceLangs( 'fr' );
		$this->assertSame( 'English Wikisource', $langs2['en'] );
		$this->assertSame( 'svenskspråkiga Wikisource', $langs2['sv'] );
		$this->assertSame( 'Wikisource multilingue', $langs2['mul'] );
		// Note that this test doesn't test the fallback to the interface language for missing local labels,
		// because this will hopefully be fixed on Wikidata and so wouldn't be repeatable.
	}
}
