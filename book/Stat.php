<?php

/**
 * @author Thomas Pellissier Tanon
 * @copyright 2012 Thomas Pellissier Tanon
 * @licence http://www.gnu.org/licenses/gpl.html GNU General Public Licence
 */
class Stat {
	public static function add( $format, $lang ) {
		if ( $format === 'epub' ) {
			$format = 'epub-2';
		}
		$stat = self::getStat();
		if ( isset( $stat[$format][$lang] ) ) {
			$stat[$format][$lang]++;
		} else {
			$stat[$format][$lang] = 1;
		}
		self::setStat( $stat );
	}

	public static function getStat( $month = 0, $year = 0 ) {
		$path = self::getStatPath( $month, $year );
		if ( file_exists( $path ) ) {
			$file = fopen( $path, 'r' );
			flock( $file, LOCK_SH );
			$data = unserialize( fread( $file, 1000000 ) );
			flock( $file, LOCK_UN );
			fclose( $file );

			return $data;
		} else {
			return [
				'epub-2' => [], 'epub-3' => [], 'odt' => [], 'xhtml' => []
			];
		}
	}

	protected static function setStat( $stat ) {
		$path = self::getStatPath();
		$file = fopen( $path, 'w' );
		flock( $file, LOCK_EX );
		ftruncate( $file, 0 );
		fwrite( $file, serialize( $stat ) );
		flock( $file, LOCK_UN );
		fclose( $file );
	}

	protected static function getStatPath( $month = 0, $year = 0 ) {
		global $wsexportConfig;
		if ( $month == 0 && $year == 0 ) {
			date_default_timezone_set( 'UTC' );
			$date = getdate();
			if ( @mkdir( $wsexportConfig['tempPath'] . '/stat' ) ) {
			}
			if ( @mkdir( $wsexportConfig['tempPath'] . '/stat/' . $date['year'] ) ) {
			}

			return $wsexportConfig['tempPath'] . '/stat/' . $date['year'] . '/' . $date['mon'] . '.sphp';
		} else {
			return $wsexportConfig['tempPath'] . '/stat/' . $year . '/' . $month . '.sphp';
		}
	}
}

