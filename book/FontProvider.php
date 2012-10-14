<?php
/**
* @author Thomas Pellissier Tanon
* @copyright 2012 Thomas Pellissier Tanon
* @licence http://www.gnu.org/licenses/gpl.html GNU General Public Licence
*/

/**
* provide data about fonts
*/
class FontProvider {

        /**
         * array key/value that contain data about fonts
         */
        protected static $data = array(
                'freeserif' => array(
                        'name' => 'FreeSerif',
                        'label' => 'Free Serif',
                        'otf' => array(
                                'R' => 'FreeSerif.otf',
                                'RB' => 'FreeSerifBold.otf',
                                'RBI' => 'FreeSerifBoldItalic.otf',
                                'RI' => 'FreeSerifItalic.otf'
                        )
                ),
                'linuxlibertine' => array(
                        'name' => 'LinuxLibertine',
                        'label' => 'Linux Libertine',
                        'otf' => array(
                                'R' => 'LinLibertine_R.otf',
                                'RB' => 'LinLibertine_RB.otf',
                                'RBI' => 'LinLibertine_RBI.otf',
                                'RI' => 'LinLibertine_RI.otf'
                        )
                )
        );

        /**
         * return data about a font
         * @return array
         */
        public static function getData($id) {
                if(isset(self::$data[$id])) {
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
                $list = array();
                foreach(self::$data as $key => $font) {
                        $list[$key] = $font['label'];
                }
                return $list;
        }

        /**
         * return CSS
         * @return string
         */
        public static function getCss($id, $basePath) {
                if(!isset(self::$data[$id])) {
                        return '';
                }
                $css = '';
                $font = self::$data[$id];
                if(isset($font['otf']['R'])) {
                        $css .= '@font-face { font-family: "' . $font['name'] . '"; font-weight: normal; font-style: normal; src: url("' . $basePath . $font['name'] . 'R.otf"); }' . "\n";
                }
                if(isset($font['otf']['RB'])) {
                        $css .= '@font-face { font-family: "' . $font['name'] . '"; font-weight: bold; font-style: normal; src: url("' . $basePath . $font['name'] . 'RB.otf"); }' . "\n";
                }
                if(isset($font['otf']['RI'])) {
                        $css .= '@font-face { font-family: "' . $font['name'] . '"; font-weight: normal; font-style: italic; src: url("' . $basePath . $font['name'] . 'RI.otf"); }' . "\n";
                }
                if(isset($font['otf']['RBI'])) {
                        $css .= '@font-face { font-family: "' . $font['name'] . '"; font-weight: bold; font-style: italic; src: url("' . $basePath . $font['name'] . 'RBI.otf"); }' . "\n";
                }
            $css .= 'body { font-family: ' . $font['name'] . ', Arial, serif; }' ."\n\n";
            return $css;
        }
}
