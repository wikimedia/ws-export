<?php
namespace App\Repository;

use App\Exception\WsExportException;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\DriverException;
use Symfony\Component\HttpFoundation\Response;
use Wikimedia\ToolforgeBundle\Service\ReplicasClient;

class CreditRepository {
	/** @var ReplicasClient */
	private $client;

	public function __construct( ReplicasClient $client ) {
		$this->client = $client;
	}

	/**
	 * Get the database name where credits need to be queried from.
	 * @param string $domain The wikisource's domain.
	 * @param string $family The wikisource's family.
	 * @return string
	 */
	public function getDatabaseName( $domain, $family ) {
		if ( $family === 'wikisource' ) {
			if ( in_array( $domain, [ '', 'old', 'www', 'mul' ] ) ) {
				$dbname = 'sourceswiki';
			} elseif ( $domain === 'zh-min-nan' ) {
				$dbname = 'zh_min_nanwikisource';
			} else {
				$dbname = $domain . $family;
			}
		} else {
			$dbname = $domain . $family;
		}
		return $dbname;
	}

	/**
	 * Get credits for given pages
	 * @param string $lang The page's language.
	 * @param array $namespaces Possible namespaces with corresponding ids.
	 * @param array $pages Pages to get credits for.
	 * @return array
	 */
	public function getPageCredits( string $lang, array $namespaces, array $pages ) : array {
		$dbname = $this->getDatabaseName( $lang, 'wikisource' );

		$connection = $this->client->getConnection( $dbname );
		$pagesByNamespace = [];

		foreach ( $pages as $page ) {
			$splitPage = explode( ":", $page, 2 );
			if ( isset( $splitPage[1] ) ) {
				$namespaceName = $splitPage[0];
				$title = $splitPage[1];
				if ( isset( $namespaces[$namespaceName] ) && $title ) {
					$namespaceId = $namespaces[$namespaceName];
					$pagesByNamespace[$namespaceId][] = $connection->quote( $title );
				} else {
					$pagesByNamespace[0][] = $connection->quote( $page );
				}

			} else {
				$pagesByNamespace[0][] = $connection->quote( $page );
			}
		}

		$whereSql = [];
		foreach ( $pagesByNamespace as $namespaceId => $pages ) {
			$whereSql[] = "( page_namespace = $namespaceId AND page_title IN (" . implode( ",", $pages ) . ")" . ")";
		}
		$whereSqlString = "(" . implode( ' OR ', $whereSql ) . ")";

		return $this->fetchAllAssociative( $connection,
				"SELECT
				actor_name,
				COUNT(rev_id) AS count,
				(
				SELECT 1
				FROM user_groups
				WHERE ug_user = user_id
					AND ug_group = 'bot'
				) AS bot
				FROM
				revision_userindex
				JOIN page ON page_id = rev_page
				JOIN actor_revision ON actor_id = rev_actor
				JOIN user ON user_id = actor_user
				WHERE
				$whereSqlString
				GROUP BY actor_name
				ORDER BY count DESC"
			);
	}

	/**
	 * Get credits for given images.
	 * @param array $images Images to get credits for.
	 * @return array
	 */
	public function getImageCredits( array $images ) : array {
		$dbname = $this->getDatabaseName( 'commons', 'wiki' );

		$connection = $this->client->getConnection( $dbname );

		$quotedImages = [];
		foreach ( $images as $image ) {
			$quotedImages[] = $connection->quote( $image );
		}
		$sqlInString = "(" . implode( ',', $quotedImages ) . ")";

		return $this->fetchAllAssociative( $connection,
				"SELECT DISTINCT actor_name, bot FROM (
					(
					  SELECT DISTINCT actor_name,
						(
						  SELECT 1
						  FROM user_groups
						  WHERE ug_user = user_id
							AND ug_group = 'bot'
						) AS bot
					  FROM
							image
						JOIN actor_image ON actor_id = img_actor
						JOIN user ON user_id = actor_user
					  WHERE
						img_name IN $sqlInString
					) UNION (
					  SELECT DISTINCT actor_name,
						(
						  SELECT 1
						  FROM user_groups
						  WHERE ug_user = user_id
							AND ug_group = 'bot'
						) AS bot
					  FROM
							oldimage
						JOIN actor_oldimage ON actor_id = oi_actor
						JOIN user ON user_id = actor_user
					  WHERE
						oi_name IN $sqlInString
					)
				  ) a"
			);
	}

	/**
	 * Runs Connection::fetchAllAssociative() on the given SQL and closes the Connection.
	 * Also captures common MySQL errors where we want to tell the user to re-try.
	 * @param Connection $connection
	 * @param string $sql
	 * @return array
	 * @throws WsExportException
	 */
	private function fetchAllAssociative( Connection $connection, string $sql ): array {
		try {
			$results = $connection->fetchAllAssociative( $sql );
			$connection->close();
		} catch ( DriverException $e ) {
			// 1226 = the 'max_user_connections' error, all others are variants of MySQL timeouts.
			// In all cases, we encourage re-trying and disabling credits if it continues to fail.
			if ( in_array( $e->getCode(), [ 1226, 1969, 2006, 2013 ] ) ) {
				throw new WsExportException( 'fetching-credits', [], Response::HTTP_SERVICE_UNAVAILABLE, false );
			}

			throw $e;
		}

		return $results ?? [];
	}
}
