<?php
/**
 * @author Thomas Pellissier Tanon
 * @copyright 2017 Thomas Pellissier Tanon
 * @license GPL-2.0-or-later
 */

/**
 * provide data about fonts
 */
class FontProvider {

	/**
	 * array key/value that contain data about fonts
	 */
	protected static $data = [
		'freeserif' => [
			'name' => 'FreeSerif', 'label' => 'Free Serif', 'css_name' => 'wse_FreeSerif', 'otf' => [
				'R' => 'FreeSerif.otf', 'RB' => 'FreeSerifBold.otf', 'RBI' => 'FreeSerifBoldItalic.otf', 'RI' => 'FreeSerifItalic.otf'
			]
		], 'linuxlibertine' => [
			'name' => 'LinuxLibertine', 'label' => 'Linux Libertine', 'css_name' => 'wse_LinuxLibertine', 'otf' => [
				'R' => 'LinLibertine_R.otf', 'RB' => 'LinLibertine_RB.otf', 'RBI' => 'LinLibertine_RBI.otf', 'RI' => 'LinLibertine_RI.otf'
			]
		], 'libertinus' => [
			'name' => 'Libertinus', 'label' => 'Libertinus', 'css_name' => 'wse_Libertinus', 'otf' => [
				'R' => 'libertinusserif-regular.otf', 'RB' => 'libertinusserif-bold.otf', 'RBI' => 'libertinusserif-bolditalic.otf', 'RI' => 'libertinusserif-italic.otf'
			]
		]
	];

	/**
	 * return data about a font
	 * @return array
	 */
	public static function getData( $id ) {
		if ( isset( self::$data[$id] ) ) {
			return self::$data[$id];
		} else {
			return null;
		}
	}

	/**
	 * return list of fonts
	 * @return array
	 */
	public static function getList() {
		$list = [];
		foreach ( self::$data as $key => $font ) {
			$list[$key] = $font['label'];
		}

		return $list;
	}

	/**
	 * return CSS
	 * @return string
	 */
	public static function getCss( $id, $basePath ) {
		if ( !isset( self::$data[$id] ) ) {
			return '';
		}
		$css = '';
		$font = self::$data[$id];
		if ( isset( $font['otf']['R'] ) ) {
			$css .= '@font-face { font-family: "' . $font['css_name'] . '"; font-weight: normal; font-style: normal; src: url("' . $basePath . $font['name'] . 'R.otf"); }' . "\n";
		}
		if ( isset( $font['otf']['RB'] ) ) {
			$css .= '@font-face { font-family: "' . $font['css_name'] . '"; font-weight: bold; font-style: normal; src: url("' . $basePath . $font['name'] . 'RB.otf"); }' . "\n";
		}
		if ( isset( $font['otf']['RI'] ) ) {
			$css .= '@font-face { font-family: "' . $font['css_name'] . '"; font-weight: normal; font-style: italic; src: url("' . $basePath . $font['name'] . 'RI.otf"); }' . "\n";
		}
		if ( isset( $font['otf']['RBI'] ) ) {
			$css .= '@font-face { font-family: "' . $font['css_name'] . '"; font-weight: bold; font-style: italic; src: url("' . $basePath . $font['name'] . 'RBI.otf"); }' . "\n";
		}

		return $css;
	}
}
