<?php

namespace App\Controller;

use App\Repository\GeneratedBookRepository;
use DateTime;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
// phpcs:ignore
use Symfony\Component\Routing\Annotation\Route;

class StatisticsController extends AbstractController {

	private function normalizeFormat( string $format ): string {
		$parts = explode( '-', $format );
		return $parts[ 0 ];
	}

	/**
	 * @param Request $request
	 * @param GeneratedBookRepository $generatedBookRepo
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	#[Route( '/statistics', name: 'statistics' )]
	#[Route( '/stat.php' )]
	public function index( Request $request, GeneratedBookRepository $generatedBookRepo ) {
		$now = new DateTime();
		$month = $request->query->get( 'month', $now->format( 'm' ) );
		$year = $request->query->get( 'year', $now->format( 'Y' ) );

		$stat = $generatedBookRepo->getTypeAndLangStats( $month, $year );

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
			'recently_popular' => $generatedBookRepo->getRecentPopular(),
			'month' => $month,
			'year' => $year,
			'val' => $val,
			'total' => $total,
		] );
	}
}
