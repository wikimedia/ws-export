<?php
$wsexportConfig = [
	'basePath' => '..', 'tempPath' => '../temp', 'stat' => true
];

function normalizeFormat( $format ) {
	$parts = explode( '-', $format );
	return $parts[0];
}

include_once __DIR__ . '/../book/init.php';

date_default_timezone_set( 'UTC' );
$date = getdate();
$month = isset( $_GET['month'] ) ? intval( $_GET['month'] ) : $date['mon'];
$year = isset( $_GET['year'] ) ? intval( $_GET['year'] ) : $date['year'];

$stat = CreationLog::singleton()->getTypeAndLangStats( $month, $year );
$val = [];
$total = [];
foreach ( $stat as $format => $temp ) {
	$format = normalizeFormat( $format );
	foreach ( $temp as $lang => $num ) {
		if ( $lang === '' ) {
			$lang = 'oldwiki';
		}

		$val[$lang][$format] += $num;
		$total[$format] += $num;
	}
}

ksort( $val );
ksort( $total );
include 'templates/stat.php';
