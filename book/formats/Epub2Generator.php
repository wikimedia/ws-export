<?php
/**
* @author Thomas Pellissier Tanon
* @copyright 2011 Thomas Pellissier Tanon
* @licence http://www.gnu.org/licenses/gpl.html GNU General Public Licence
*/

/**
* create an epub 2 file
* @see http://idpf.org/epub/201
*/
class Epub2Generator implements Generator {

        /**
        * return the extension of the generated file
        * @return string
        */
        public function getExtension() {
                return 'epub';
        }

        /**
        * create the file
        * @var $data Book the title of the main page of the book in Wikisource
        * @return 
        * @todo
        */
        public function create(Book $data) {
                return null;
        }

        /**
        * send the file previously created with good headers
        * @var $file The file
        * @return 
        * @todo
        */
        public function send($file) {
                echo $file;
        }
}
