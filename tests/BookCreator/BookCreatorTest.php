<?php

namespace App\Tests\BookCreator;

use App\Book;
use App\BookCreator;
use App\BookProvider;
use App\Generator\FormatGenerator;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\BookCreator
 */
class BookCreatorTest extends TestCase {
	private $bookCreator;
	private $bookProvider;
	private $bookGenerator;

	public function setUp(): void {
		$this->bookProvider = $observer = $this->getMockBuilder( BookProvider::class )->disableOriginalConstructor()->getMock();
		$this->bookGenerator = $observer = $this->getMockBuilder( FormatGenerator::class )->getMock();
		$this->bookCreator = new BookCreator( $this->bookProvider, $this->bookGenerator );
	}

	public function testCreate() {
		$book = new Book();
		$this->bookProvider->expects( $this->once() )
			->method( 'get' )
			->with( 'Test' )
			->willReturn( $book );

		$this->bookGenerator->expects( $this->once() )
			->method( 'create' )
			->with( $book )
			->willReturn( 'path' );

		$this->bookCreator->create( 'Test' );
		$this->assertEquals( 'path', $this->bookCreator->getFilePath() );
		$this->assertSame( $book, $this->bookCreator->getBook() );
	}
}
