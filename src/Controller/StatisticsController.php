<?php

namespace App\Controller;

use App\CreationLog;
use DateTime;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class StatisticsController extends AbstractController {

	private function normalizeFormat( string $format ): string {
		$parts = explode( '-', $format );
		return $parts[ 0 ];
	}

	/**
	 * @Route("/statistics", name="statistics")
	 * @Route("/stat.php")
	 */
	public function index( Request $request, CreationLog $creationLog ) {
		$now = new DateTime();
		$month = $request->get( 'month', $now->format( 'm' ) );
		$year = $request->get( 'year', $now->format( 'Y' ) );

		try {
			$stat = $creationLog->getTypeAndLangStats( $month, $year );
		} catch ( Exception $e ) {
			$this->addFlash( 'danger', 'Internal error: ' . $e->getMessage() );
			$stat = [];
		}

		$val = [];
		$total = [];
		foreach ( $stat as $format => $temp ) {
			$format = $this->normalizeFormat( $format );
			foreach ( $temp as $lang => $num ) {
				if ( $lang === '' ) {
					$lang = 'oldwiki';
				}
				if ( !array_key_exists( $lang, $val ) ) {
					$val[ $lang ] = [];
				}
				if ( !array_key_exists( $format, $val[ $lang ] ) ) {
					$val[ $lang ][ $format ] = 0;
				}
				$val[ $lang ][ $format ] += $num;

				if ( !array_key_exists( $format, $total ) ) {
					$total[ $format ] = 0;
				}
				$total[ $format ] += $num;
			}
		}

		ksort( $val );
		ksort( $total );

		return $this->render( 'statistics.html.twig', [
			'recently_popular' => $creationLog->getRecentPopular(),
			'month' => $month,
			'year' => $year,
			'val' => $val,
			'total' => $total,
		] );
	}
}
