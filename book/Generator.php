<?php
/**
* @author Thomas Pellissier Tanon
* @copyright 2011 Thomas Pellissier Tanon
* @licence http://www.gnu.org/licenses/gpl.html GNU General Public Licence
*/

/**
* interface for classes creating file
*/
interface Generator {

        /**
        * return the extension of the generated file
        * @return string
        */
        public function getExtension();

        /**
        * create the file
        * @var $data Book the title of the main page of the book in Wikisource
        * @return the file
        */
        public function create(Book $data);

        /**
        * send the file previously created with good headers
        * @var $file The file
        * @var $fileName The name of the file to return (without extension)
        */
        public function send($file, $fileName);
}
