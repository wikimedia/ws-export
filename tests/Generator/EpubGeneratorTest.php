<?php

namespace App\Tests\Generator;

use App\Book;
use App\FileCache;
use App\FontProvider;
use App\Generator\EpubGenerator;
use App\Util\Api;
use Krinkle\Intuition\Intuition;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\NullAdapter;

/**
 * @coversDefaultClass \App\Generator\EpubGenerator
 */
class EpubGeneratorTest extends TestCase {
	private function getEpubGenerator( FontProvider $fontProvider ): EpubGenerator {
		return new EpubGenerator(
			$fontProvider,
			$this->createMock( Api::class ),
			$this->createMock( Intuition::class ),
			new NullAdapter(),
			$this->createMock( FileCache::class )
		);
	}

	/**
	 * @covers ::getCss
	 */
	public function testGetCss__withBookFont(): void {
		$book = new Book();
		$bookFont = 'some-unique-book-font';
		$book->options['fonts'] = $bookFont;

		$expectedCss = 'mary had a little lamb 341346 correct horse battery staple';
		$fontProvider = $this->createMock( FontProvider::class );
		$fontProvider
			->expects( $this->atLeastOnce() )
			->method( 'getCss' )
			->with( $bookFont )
			->willReturn( $expectedCss );
		$generator = $this->getEpubGenerator( $fontProvider );
		$this->assertStringContainsString(
			$expectedCss,
			$generator->getCss( $book )
		);
	}

	/**
	 * @covers ::getCss
	 */
	public function testGetCss__withoutBookFont(): void {
		$book = new Book();

		$providerCss = 'mary had a little lamb 341346 correct horse battery staple';
		$fontProvider = $this->createMock( FontProvider::class );
		$fontProvider->expects( $this->never() )->method( 'getCss' )->willReturn( $providerCss );
		$generator = $this->getEpubGenerator( $fontProvider );
		// The soft expectation of $this->never() should already be enough anyway.
		$this->assertStringNotContainsString(
			$providerCss,
			$generator->getCss( $book )
		);
	}
}
