<?php

namespace App\Tests\Repository;

use App\Book;
use App\Entity\GeneratedBook;
use App\Repository\GeneratedBookRepository;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class GeneratedBookRepositoryTest extends KernelTestCase {

	/** @var GeneratedBook */
	protected $log;

	/** @var EntityManager */
	protected $entityManager;

	/** @var GeneratedBookRepository */
	private $genBookRepo;

	protected function setUp(): void {
		parent::setUp();
		self::bootKernel();
		$this->entityManager = self::$container->get( 'doctrine' )->getManager();
		$this->genBookRepo = $this->entityManager
			->getRepository( GeneratedBook::class );
	}

	/**
	 * @covers GeneratedBookRepository::getTypeAndLangStats
	 */
	public function testAddAndRetrieve() {
		// Make sure it's empty to start with.
		static::assertEmpty(
			$this->genBookRepo->getTypeAndLangStats( date( 'n' ), date( 'Y' ) )
		);

		// Test book.
		$testBook = new Book();
		$testBook->lang = 'en';
		$testBook->title = 'Test &quot;Book&quot;';

		// Add three entries.
		$this->entityManager->persist( new GeneratedBook( $testBook, 'epub' ) );
		$this->entityManager->persist( new GeneratedBook( $testBook, 'pdf' ) );
		$this->entityManager->persist( new GeneratedBook( $testBook, 'epub' ) );
		$this->entityManager->flush();

		// Test stats.
		static::assertEquals(
			[ 'epub' => [ 'en' => 2 ], 'pdf' => [ 'en' => 1 ] ],
			$this->genBookRepo->getTypeAndLangStats( date( 'n' ), date( 'Y' ) )
		);
		// Test data.
		/** @var GeneratedBook $firstLog */
		$firstLog = $this->genBookRepo->findOneBy( [] );
		static::assertEquals(
			'Test "Book"',
			$firstLog->getTitle()
		);
	}

	/**
	 * Passing invalid strings for month or year shouldn't give incorrect results.
	 *
	 * @link https://phabricator.wikimedia.org/T290674
	 * @covers \App\Repository\GeneratedBookRepository::getTypeAndLangStats()
	 */
	public function testInvalidDateParams(): void {
		$noStats = $this->genBookRepo->getTypeAndLangStats( 'foo', 'bar' );
		$this->assertEmpty( $noStats );

		$testBook = new Book();
		$testBook->lang = 'en';
		$testBook->title = 'Test &quot;Book&quot;';
		$this->entityManager->persist( new GeneratedBook( $testBook, 'epub' ) );
		$this->entityManager->flush();

		$oneStat = $this->genBookRepo->getTypeAndLangStats( date( 'n' ), date( 'Y' ) );
		$this->assertCount( 1, $oneStat );

		$invalidMonthStat = $this->genBookRepo->getTypeAndLangStats( '09 AND 1=1', date( 'Y' ) );
		$this->assertEmpty( $invalidMonthStat );
	}
}
