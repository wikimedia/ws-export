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
	 * @var int PDO timeout in seconds
	 * For sqlite, it is the time to get a file lock, so it should not block the import operation.
	 */
	private const PDO_TIMEOUT = 2;

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
	 * Get top 20 popular books in the last 3 months.
	 */
	public function getRecentPopular() {
		$sql = 'SELECT IF (`lang` = "", "www", `lang`) AS `lang`, `title`, COUNT(*) AS `total`'
			. ' FROM `' . $this->getTableName() . '`'
			. ' WHERE `time` > DATE_SUB( CURRENT_DATE, INTERVAL 3 month)'
			. ' GROUP BY `lang`, `title`'
			. ' ORDER BY `total` DESC'
			. ' LIMIT 20';
		return $this->pdo->query( $sql )->fetchAll();
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
		// Log entries can be duplicated where there was more than one request for the same ebook within the same second.
		$all = $fileDb->query( 'SELECT *, COUNT(*) AS `total` FROM `creation` GROUP BY `lang`, `title`, `format`, `time`' );
		// phpcs:ignore
		while ( $row = $all->fetch() ) {
			$params = [
				'lang' => $row['lang'],
				// Title is stored with HTML entities in Sqlite, so we're converting to the proper form on inport.
				'title' => htmlspecialchars_decode( $row['title'] ),
				'format' => $row['format'],
				'time' => $row['time'],
			];
			// Count the existing rows in the target database.
			$selectSql = 'SELECT *, COUNT(*) AS `total` FROM `' . $this->getTableName() . '` WHERE'
				. ' `lang` = :lang'
				. ' AND `title` = :title'
				. ' AND `format` = :format'
				. ' AND `time` = :time';
			$findStmt = $this->pdo->prepare( $selectSql );
			$findStmt->execute( $params );
			$found = $findStmt->fetch();
			if ( $found['total'] === $row['total'] ) {
				// If all of the rows already exist, continue to the next.
				continue;
			}
			// Insert the new rows.
			for ( $i = 0; $i < $row['total']; $i++ ) {
				$insertSql = 'INSERT INTO `' . $this->getTableName() . '`'
					. ' (`lang`, `title`, `format`, `time`)'
					. ' VALUES (:lang, :title, :format, :time);';
				$this->pdo->prepare( $insertSql )->execute( $params );
			}
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
		$pdo->setAttribute( PDO::ATTR_TIMEOUT, self::PDO_TIMEOUT );
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
