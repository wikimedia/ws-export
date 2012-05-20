<?php
/**
* @author Thomas Pellissier Tanon
* @copyright 2011 Thomas Pellissier Tanon
* @licence http://www.gnu.org/licenses/gpl.html GNU General Public Licence
*/

/**
* container for all the data on a book
*/
class Book extends Page {

        /**
        * language of the book like 'en' or 'fr'
        */
        public $lang = '';

        /**
        * output options
        */
        public $options = array();

        /**
        * meatadata on the book
        * @see https://wikisource.org/wiki/Wikisource:Microformat
        */
        public $type = '';
        public $author = '';
        public $translator = '';
        public $illustrator = '';
        public $school = '';
        public $publisher = '';
        public $year = '';
        public $place = '';
        public $key = '';
        public $progress = '';
        public $volume = '';
        public $scan = '';
        public $cover = '';

        /**
        * list of the categories as string object like array('1859', 'France')
        */
        public $categories = array();

        /**
        * pictures included in the page
        * @type array
        */
        public $pictures = array();

        /**
        * list of contributors of the book array('PSEUDO' => array('flags' => array(), 'count' => integer))
        */
        public $credits = array();
}
