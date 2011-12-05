<?php
/**
* @author Thomas Pellissier Tanon
* @copyright 2011 Thomas Pellissier Tanon
* @licence http://www.gnu.org/licenses/gpl.html GNU General Public Licence
*/

/**
* container for a page of Wikisource
*/
class Page {

        /**
        * title of the book in Wikisource
        */
        public $title = '';

        /**
        * name to display
        */
        public $name = '';

        /**
        * content of the page
        * @type DOMDocument
        */
        public $content = null;
}
