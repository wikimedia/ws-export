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
        * return the mimetype of the generated file
        * @return string
        */
        public function getMimeType() {
                return 'application/epub+zip';
        }

        /**
        * create the file
        * @var $data Book the title of the main page of the book in Wikisource
        * @return 
        * @todo
        */
        public function create(Book $book) {
                $zip = new ZipCreator();
                $zip->addContentFile('mimetype', 'application/epub+zip');
                if($book->summary != null) {
                        foreach($data->chapters as $chapter) {
                                $zip->addContentFile($chapter->title . '.xhtml', $chapter->content->saveXML());
                        }
                } else {
                        $zip->addContentFile($book->title . '.xhtml', $book->content->saveXML());
                }
                return $zip->getContent();
        }
}

