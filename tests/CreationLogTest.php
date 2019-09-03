<?php

namespace App\Tests;

use App\Book;
use App\CreationLog;
use PDO;
use PHPUnit\Framework\TestCase;

class CreationLogTest extends TestCase {

	/** @var CreationLog */
	protected $log;

	public function setUp(): void {
		parent::setUp();
		$this->log = CreationLog::singleton();
		// Drop table on setup because of annoying interaction from tests such as StatTest which recreate it.
		$this->dropTable();
	}

	public function tearDown(): void {
		$this->dropTable();
		parent::tearDown();
	}

	protected function dropTable() {
		$dropTableSql = "DROP TABLE IF EXISTS `" . $this->log->getTableName() . "`";
		$this->log->getPdo()->exec( $dropTableSql );
	}

	/**
	 * @covers \App\CreationLog::createTable
	 * @covers \App\CreationLog::getTypeAndLangStats
	 */
	public function testCreation() {
		// Make sure there's no tables in the database.
		$showTableSql = 'SHOW TABLES LIKE "' . $this->log->getTableName() . '"';
		$tableList = $this->log->getPdo()->query( $showTableSql )->fetch();
		static::assertFalse( $tableList );

		// Create the table, and check that it's there.
		$createTable = $this->log->createTable();
		static::assertSame( 0, $createTable );
		$tableList = $this->log->getPdo()->query( $showTableSql )->fetch();
		static::assertCount( 2, $tableList );

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
			$this->log->getPdo()->query( 'SELECT title FROM ' . $this->log->getTableName() . ' LIMIT 1' )->fetch()['title']
		);
	}

	/**
	 * @covers \App\CreationLog::import
	 */
	public function testImport() {
		$this->log->createTable();

		// Insert some test data into the file database.
		$fileDb = $this->log->getPdo( 'file' );
		$fileDb->exec( 'DROP TABLE IF EXISTS `creation`' );
		$fileDb->exec( 'CREATE TABLE IF NOT EXISTS `creation` (
			`lang` VARCHAR(40) NOT NULL,
			`title` VARCHAR(200) NOT NULL,
			`format` VARCHAR(10) NOT NULL,
			`time` DATETIME  NOT NULL
		);' );
		$fileDb->exec( 'INSERT INTO `creation` (`lang`, `title`, `format`, `time`) VALUES '
			. "( 'en', 'Test 1', 'epub', '2019-06-14 01:00:00' ),"
			. "( 'en', 'Test 1', 'epub', '2019-06-14 01:00:00' ),"
			. "( 'en', 'Test 1', 'epub', '2019-06-14 01:00:00' ),"
			. "( 'en', 'Test 2 Iñtërnâtiônàlizætiøn', 'pdf', '2019-06-15 02:00:00' ),"
			. "( 'fr', 'Test &quot;3 &amp; 4&quot;', 'epub', '2019-06-16 03:00:00' ),"
			. "( 'pt', 'Test 5', 'epub', '2019-07-14 01:00:00' ),"
			. "( 'pt', 'Test 5', 'epub', '2019-07-14 01:00:00' )"
		);

		// Check initial state of destination database.
		static::assertEquals( [], $this->log->getTypeAndLangStats( '6', '2019' ) );
		// Run import.
		$this->log->import();
		// Check results.
		static::assertEquals( [ 'epub' => [ 'en' => 3, 'fr' => 1 ], 'pdf' => [ 'en' => 1 ] ], $this->log->getTypeAndLangStats( '6', '2019' ) );
		$selectSql = 'SELECT title FROM ' . $this->log->getTableName();
		static::assertEquals(
			[ [ 'Test 1' ], [ 'Test 1' ], [ 'Test 1' ], [ 'Test 2 Iñtërnâtiônàlizætiøn' ], [ 'Test "3 & 4"' ], [ 'Test 5' ], [ 'Test 5' ] ],
			$this->log->getPdo()->query( $selectSql )->fetchAll( PDO::FETCH_NUM )
		);

		// Clean up.
		$fileDb->exec( 'DROP TABLE `creation`' );
	}
}
