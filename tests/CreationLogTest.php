<?php

namespace App\Tests;

use App\Book;
use App\CreationLog;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class CreationLogTest extends KernelTestCase {

	/** @var CreationLog */
	protected $log;

	/** @var Connection */
	protected $db;

	protected function setUp(): void {
		parent::setUp();
		self::bootKernel();
		$this->db = self::$container->get( 'doctrine.dbal.default_connection' );
		$this->log = self::$container->get( CreationLog::class );
		$this->dropTable();
	}

	public function tearDown(): void {
		$this->dropTable();
		parent::tearDown();
	}

	protected function dropTable() {
		$dropTableSql = "DROP TABLE IF EXISTS `" . $this->log->getTableName() . "`";
		$this->db->query( $dropTableSql );
	}

	/**
	 * @covers \App\CreationLog::createTable
	 * @covers \App\CreationLog::getTypeAndLangStats
	 */
	public function testCreation() {
		// Make sure there's no tables in the database.
		$showTableSql = 'SHOW TABLES LIKE "' . $this->log->getTableName() . '"';
		$tableList = $this->db->query( $showTableSql )->fetchAll();
		static::assertEmpty( $tableList );

		// Create the table, and check that it's there.
		$createTable = $this->log->createTable();
		static::assertSame( 0, $createTable );
		$tableList = $this->db->query( $showTableSql )->fetchAll();
		static::assertCount( 1, $tableList );

		// Make sure it's empty.
		static::assertEquals( [], $this->log->getTypeAndLangStats( date( 'n' ), date( 'Y' ) ) );
	}

	/**
	 * @covers \App\CreationLog::add
	 * @covers \App\CreationLog::getTypeAndLangStats
	 */
	public function testAddAndRetrieve() {
		$this->log->createTable();

		// Make sure it's empty to start with.
		static::assertEquals( [], $this->log->getTypeAndLangStats( date( 'n' ), date( 'Y' ) ) );

		// Test book.
		$testBook = new Book();
		$testBook->lang = 'en';
		$testBook->title = 'Test &quot;Book&quot;';

		// Add three entries.
		$this->log->add( $testBook, 'epub' );
		$this->log->add( $testBook, 'pdf' );
		$this->log->add( $testBook, 'epub' );

		// Test stats.
		static::assertEquals(
			[ 'epub' => [ 'en' => 2 ], 'pdf' => [ 'en' => 1 ] ],
			$this->log->getTypeAndLangStats( date( 'n' ), date( 'Y' ) )
		);
		// Test data.
		static::assertEquals(
			'Test "Book"',
			$this->db->query( 'SELECT title FROM ' . $this->log->getTableName() . ' LIMIT 1' )->fetch()['title']
		);
	}
}
