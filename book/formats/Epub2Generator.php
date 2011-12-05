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
        public function create(Book $book) {
                $zip = new ZipCreator();
                $zip->addContentFile('mimetype', 'application/epub+zip');
                if($book->summary != null) {
                        foreach($data->chapters as $chapter) {
                                $zip->addContentFile($chapter->title . '.html', $chapter->content->saveHTML());
                        }
                } else {
                        $zip->addContentFile($book->title . '.html', $book->content->saveHTML());
                }
                return $zip->getContent();
        }

        /**
        * send the file previously created with good headers
        * @var $file The file
        * @var $fileName The name of the file to return (without extension)
        */
        public function send($file, $fileName) {
                header('Content-Type: application/epub+zip');
                header('Content-Disposition: attachment;filename="' . $fileName . '.epub"');
                header('Content-Description: File Transfert');
                header('Content-Transfer-Encoding: binary');
                header('Content-length: ' . strlen($file));
                echo $file;
                flush();
        }
}
