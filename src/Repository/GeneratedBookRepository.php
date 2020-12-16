<?php

namespace App\Repository;

use App\Entity\GeneratedBook;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Interface to the database for the GeneratedBook entity.
 */
class GeneratedBookRepository extends ServiceEntityRepository {

	public function __construct( ManagerRegistry $registry ) {
		parent::__construct( $registry, GeneratedBook::class );
	}

	/**
	 * Get top 20 popular books in the last 3 months.
	 * @return array
	 */
	public function getRecentPopular(): array {
		$sql = 'SELECT IF(`lang` = "", "www", `lang`) AS `lang`, `title`, COUNT(*) AS `total`'
			. ' FROM books_generated'
			. ' WHERE `time` > DATE_SUB(CURRENT_DATE, INTERVAL 3 month)'
			. ' GROUP BY `lang`, `title`'
			. ' ORDER BY `total` DESC'
			. ' LIMIT 20';

		return $this->getEntityManager()
			->getConnection()
			->executeQuery( $sql )
			->fetchAllAssociative();
	}

	/**
	 * Get total counts of exports by type and language.
	 * @param string $month The month number.
	 * @param string $year The year.
	 * @return int[][] Total counts, keyed by format and then language code.
	 */
	public function getTypeAndLangStats( string $month, string $year ): array {
		$sql = 'SELECT `format`, `lang`, COUNT(1) AS `number` '
			. ' FROM books_generated'
			. ' WHERE YEAR(`time`) = :year AND MONTH(`time`) = :month'
			. ' GROUP BY `format`, `lang`';
		$cursor = $this->getEntityManager()
			->getConnection()
			->executeQuery( $sql, [
				'month' => $month,
				'year' => $year,
			] )
			->fetchAllAssociative();

		$stats = [];
		foreach ( $cursor as $row ) {
			$stats[$row['format']][$row['lang']] = (int)$row['number'];
		}

		return $stats;
	}
}
