<?php

namespace App;

use Doctrine\DBAL\Connection;

/**
 * @author Thomas Pellissier Tanon
 * @copyright 2012 Thomas Pellissier Tanon
 * @license GPL-2.0-or-later
 */
class CreationLog {

	/** @var bool */
	private $enableStats;

	/**
	 * @var Connection
	 */
	private $db;

	/** @var string The name of the database table. */
	protected $tableName = 'books_generated';

	public function __construct( bool $enableStats, Connection $connection ) {
		$this->db = $connection;
		$this->enableStats = $enableStats;
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
		return $this->db->exec(
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
		if ( !$this->enableStats ) {
			return;
		}
		$this->db->prepare(
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
		return $this->db->query( $sql )->fetchAll();
	}

	/**
	 * Get total counts of exports by type and language.
	 * @param string $month The month number.
	 * @param string $year The year.
	 * @return int[][] Total counts, keyed by format and then language code.
	 */
	public function getTypeAndLangStats( $month, $year ) {
		$cursor = $this->db->prepare(
			'SELECT `format`, `lang`, count(1) AS `number` FROM `' . $this->getTableName() . '`'
			. ' WHERE YEAR(`time`) = :year AND MONTH(`time`) = :month GROUP BY `format`, `lang`'
		);
		$cursor->bindParam( 'year', $year );
		$cursor->bindParam( 'month', $month );
		$result = $cursor->execute();

		$stats = [];
		foreach ( $result->fetchAllAssociative() as $row ) {
			$stats[$row['format']][$row['lang']] = (int)$row['number'];
		}

		return $stats;
	}

}
