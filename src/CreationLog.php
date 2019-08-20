<?php

namespace App;

use PDO;

/**
 * @author Thomas Pellissier Tanon
 * @copyright 2012 Thomas Pellissier Tanon
 * @license GPL-2.0-or-later
 */
class CreationLog {

	/**
	 * @var PDO
	 */
	private $pdo;

	/** @var string The name of the database table. */
	protected $tableName = 'books_generated';

	private function __construct() {
		$this->pdo = $this->getPdo();
	}

	/**
	 * @return string
	 */
	public function getTableName() {
		return $this->tableName;
	}

	/**
	 * @return int|bool
	 */
	public function createTable() {
		return $this->pdo->exec(
			'CREATE TABLE IF NOT EXISTS `' . $this->getTableName() . '` (
				`time` DATETIME(2) NOT NULL,
				INDEX time (`time`),
				`lang` VARCHAR(10) CHARACTER SET utf8mb4 NOT NULL,
				INDEX lang (`lang`),
				`title` VARBINARY(255) NOT NULL,
				`format` VARCHAR(10) CHARACTER SET utf8mb4 NOT NULL
			) DEFAULT CHARSET=utf8mb4;'
		);
	}

	public function add( Book $book, $format ) {
		global $wsexportConfig;
		if ( !$wsexportConfig['stat'] ) {
			return;
		}
		$this->pdo->prepare(
			'INSERT INTO `' . $this->getTableName() . '` (`lang`, `title`, `format`, `time`) VALUES (:lang, :title, :format, NOW());'
		)->execute( [
			'lang' => $book->lang, 'title' => htmlspecialchars_decode( $book->title ), 'format' => $format
		] );
	}

	/**
	 * Get total counts of exports by type and language.
	 * @param string $month The month number.
	 * @param string $year The year.
	 * @return int[][] Total counts, keyed by format and then language code.
	 */
	public function getTypeAndLangStats( $month, $year ) {
		$cursor = $this->pdo->prepare(
			'SELECT `format`, `lang`, count(1) AS `number` FROM `' . $this->getTableName() . '`'
			. ' WHERE YEAR(`time`) = :year AND MONTH(`time`) = :month GROUP BY `format`, `lang`'
		);
		$cursor->execute( [ 'year' => $year, 'month' => $month ] );

		$stats = [];
		foreach ( $cursor as $row ) {
			$stats[$row['format']][$row['lang']] = (int)$row['number'];
		}

		return $stats;
	}

	/**
	 * Import from old Sqlite database whose path is defined in $wsexportConfig['logDatabase'].
	 * @TODO This can be deleted after all historical log files have been imported.
	 */
	public function import(): void {
		$fileDb = $this->getPdo( 'file' );
		$all = $fileDb->query( 'SELECT * FROM `creation`' );
		// phpcs:ignore
		while ( $row = $all->fetch() ) {
			$params = [
				'lang' => $row['lang'],
				// Title is stored with HTML entities in Sqlite, so we're converting to the proper form on inport.
				'title' => htmlspecialchars_decode( $row['title'] ),
				'format' => $row['format'],
				'time' => $row['time'],
			];
			// Find any existing row.
			$selectSql = 'SELECT * FROM `' . $this->getTableName() . '` WHERE'
				. ' `lang` = :lang'
				. ' AND `title` = :title'
				. ' AND `format` = :format'
				. ' AND `time` = :time';
			$findStmt = $this->pdo->prepare( $selectSql );
			$findStmt->execute( $params );
			if ( $findStmt->rowCount() ) {
				// If the row already exists, continue to the next.
				continue;
			}
			// Insert the new row.
			$insertSql = 'INSERT INTO `' . $this->getTableName() . '`'
				. ' (`lang`, `title`, `format`, `time`)'
				. ' VALUES (:lang, :title, :format, :time);';
			$this->pdo->prepare( $insertSql )->execute( $params );
		}
	}

	/**
	 * @param string $type One of: 'db', 'file', or 'memory'.
	 * @return PDO
	 */
	public function getPdo( $type = 'db' ): PDO {
		global $wsexportConfig;
		if ( $type === 'db' && isset( $wsexportConfig['dbDsn'] ) ) {
			$pdo = new PDO( $wsexportConfig['dbDsn'], $wsexportConfig['dbUser'], $wsexportConfig['dbPass'] );
		} elseif ( $type === 'file' && $wsexportConfig['stat'] && $wsexportConfig['logDatabase'] ) {
			$pdo = new PDO( 'sqlite:' . $wsexportConfig['logDatabase'] );
		} else {
			$pdo = new PDO( 'sqlite::memory:' );
		}
		$pdo->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
		return $pdo;
	}

	public static function singleton() {
		static $self;

		if ( $self === null ) {
			$self = new CreationLog();
		}

		return $self;
	}
}
