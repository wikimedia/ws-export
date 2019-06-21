<?php

require_once dirname( __DIR__ ) . '/bootstrap.php';

use App\CreationLog;

function normalizeFormat( $format ) {
	$parts = explode( '-', $format );
	return $parts[0];
}

date_default_timezone_set( 'UTC' );
$date = getdate();
$month = isset( $_GET['month'] ) ? (int)$_GET['month'] : $date['mon'];
$year = isset( $_GET['year'] ) ? (int)$_GET['year'] : $date['year'];

$stat = CreationLog::singleton()->getTypeAndLangStats( $month, $year );
$val = [];
$total = [];
foreach ( $stat as $format => $temp ) {
	$format = normalizeFormat( $format );
	foreach ( $temp as $lang => $num ) {
		if ( $lang === '' ) {
			$lang = 'oldwiki';
		}
		if ( !array_key_exists( $lang, $val ) ) {
			$val[$lang] = [];
		}
		if ( !array_key_exists( $format, $val[$lang] ) ) {
			$val[$lang][$format] = 0;
		}
		$val[$lang][$format] += $num;

		if ( !array_key_exists( $format, $total ) ) {
			$total[$format] = 0;
		}
		$total[$format] += $num;
	}
}

ksort( $val );
ksort( $total );
include 'templates/stat.php';
