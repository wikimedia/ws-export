<?php
namespace App\Repository;

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

		$results = $connection->fetchAllAssociative(
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
		$connection->close();

		return $results ? $results : [];
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

		$results = $connection->fetchAllAssociative(
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
		$connection->close();

		return $results ? $results : [];
	}
}
