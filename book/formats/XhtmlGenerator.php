<?php
/**
* @author Thomas Pellissier Tanon
* @copyright 2011 Thomas Pellissier Tanon
* @licence http://www.gnu.org/licenses/gpl.html GNU General Public Licence
*/

/**
* create an an xhtml file
* @see http://www.w3.org/TR/html5/
*/
class XhtmlGenerator implements FormatGenerator {

        /**
        * return the extension of the generated file
        * @return string
        */
        public function getExtension() {
                return 'xhtml';
        }

        /**
        * return the mimetype of the generated file
        * @return string
        */
        public function getMimeType() {
                return 'application/xhtml+xml';
        }

        /**
        * create the file
        * @var $data Book the title of the main page of the book in Wikisource
        * @return string
        */
        public function create(Book $book) {
                $content = $this->compile($book);
                return $this->getXhtmlContent($book, $content->saveXML());
        }

        /**
        * add metadata to xhml page
        * @return string
        */
        protected function getXhtmlContent($book, $content) {
                $head = '<head profile="http://dublincore.org/documents/dcq-html/">
                                <meta http-equiv="Content-Type" content="application/xhtml+xml; charset=UTF-8" />  
                                <title>' . $book->title . '</title>
                                <link rel="schema.DC" href="http://purl.org/dc/elements/1.1/" />
                                <link rel="schema.DCTERMS" href="http://purl.org/dc/terms/" />
                                <meta name="DC.identifier" scheme="DCTERMS.URI" content="' . wikisourceUrl($book->lang, $book->title) . '" />
                                <meta name="DC.language" content="' . $book->lang . '" />
                                <meta name="DC.title" content="' . $book->name . '" />
                                <meta name="DC.publisher" content="' . $book->publisher . '" />
                                <meta name="DC.creator" content="' . $book->author . '" />
                                <link rel="DC.source" href="' . wikisourceUrl($book->lang, $book->title) . '" />
                                <link rel="DC.rights" href="http://creativecommons.org/licenses/by-sa/3.0/" />
                                <link rel="DC.rights" href="http://www.gnu.org/copyleft/fdl.html" />
                                <meta name="DC.format" scheme="DCTERMS.IMT" content="application/xhtml+xml" />
                                <meta name="DC.type" scheme="DCTERMS.DCMIType" content="Text" />
                        </head>';
                return str_replace('<body>', $head . '<body>', $content);
        }

        /**
        * create a single DOMDocument for all the pages of the book
        * @return DOMDocument
        */
        protected function compile(Book $book) {
                if(!empty($book->chapters)) {
                        $content = $book->chapters[0]->content;
                        $contentBody = $content->getElementsByTagName('body')->item(0);
                        foreach($book->chapters as $chapter) {
                                $body = $chapter->content->getElementsByTagName('body')->item(0);
                                foreach($body->childNodes as $node) {
                                        $node = $content->importNode($node, true);
                                        $contentBody->appendChild($node);
                                }
                                $pageBreak = $content->createElement('div');
                                $pageBreak->setAttribute('style', 'page-break-before:always;');
                                $contentBody->appendChild($pageBreak);
                        }
                } else {
                        $content = $book->content;
                }
                return $content;
        }
}

