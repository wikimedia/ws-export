<?php
/**
* @author Thomas Pellissier Tanon
* @copyright 2011 Thomas Pellissier Tanon
* @licence http://www.gnu.org/licenses/gpl.html GNU General Public Licence
*/

/**
* container for a picure included in a page
*/
class Picture {

        /**
        * title of the picture, this is a sort of uid, different picture
        * get different title.
        */
        public $title = '';

        /**
        * name of the picture, this is identical to the File: name of the
        * image. Different picture can get the same title (thumb
        * of different size of the same image).
        */
        public $name = '';

        /**
        * url to the picture
        */
        public $url = '';

        /**
        * mimetype of the picture
        */
        public $mimetype = '';

        /**
        * content of the picture
        * @type string
        */
        public $content = null;
}
