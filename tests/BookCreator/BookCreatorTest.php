<?php

namespace App\Tests\BookCreator;

use App\Book;
use App\BookCreator;
use App\BookProvider;
use App\Generator\FormatGenerator;
use PHPUnit\Framework\TestCase;

/**
 * @covers BookCreator
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
			->with( $this->equalTo( 'Test' ) )
			->will( $this->returnValue( $book ) );

		$this->bookGenerator->expects( $this->once() )
			->method( 'create' )
			->with( $this->equalTo( $book ) )
			->will( $this->returnValue( 'path' ) );

		list( $returnedBook, $file ) = $this->bookCreator->create( 'Test' );
		$this->assertEquals( 'path', $file );
		$this->assertSame( $book, $returnedBook );
	}
}
