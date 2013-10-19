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
                return $this->getXhtmlContent($book, $content)->saveXML();
        }

        /**
        * add metadata to xhml page
        * @return DOMDocument
        */
        protected function getXhtmlContent(Book $book, DOMDocument $content) {
                $title = $content->getElementsByTagName('title')->item(0);
                $title->nodeValue = $book->title;
                $head = $content->getElementsByTagName('head')->item(0);
                $head->setAttribute('profile', 'http://dublincore.org/documents/dcq-html/');
                $this->addDublinCoreLinkData($content, $head, 'schema.DCTERMS', 'http://purl.org/dc/terms/');
                $this->addDublinCoreMetaData($content, $head, 'DC.identifier', wikisourceUrl($book->lang, $book->title), 'DCTERMS.URI');
                $this->addDublinCoreMetaData($content, $head, 'DC.language', $book->lang);
                $this->addDublinCoreMetaData($content, $head, 'DC.title', $book->name);
                $this->addDublinCoreMetaData($content, $head, 'DC.publisher', $book->publisher);
                $this->addDublinCoreMetaData($content, $head, 'DC.creator', $book->author);
                $this->addDublinCoreLinkData($content, $head, 'DC.source', wikisourceUrl($book->lang, $book->title));
                $this->addDublinCoreLinkData($content, $head, 'DC.rights', 'http://creativecommons.org/licenses/by-sa/3.0/');
                $this->addDublinCoreLinkData($content, $head, 'DC.rights', 'http://www.gnu.org/copyleft/fdl.html');
                $this->addDublinCoreMetaData($content, $head, 'DC.format', 'application/xhtml+xml', 'DCTERMS.IMT');
                $this->addDublinCoreMetaData($content, $head, 'DC.type', 'Text', 'DCTERMS.DCMIType');
                return $content;
        }

        protected function addDublinCoreMetaData(DOMDocument $dom, DOMElement $head, $name, $content, $scheme = '') {
                $node = $dom->createElement('meta');
                $node->setAttribute('name', $name);
                $node->setAttribute('content', $content);
                if($scheme !== '') {
                        $node->setAttribute('scheme', $scheme);
                }
                $head->appendChild($node);
        }

        protected function addDublinCoreLinkData(DOMDocument $dom, DOMElement $head, $rel, $href) {
                $node = $dom->createElement('link');
                $node->setAttribute('rel', $rel);
                $node->setAttribute('href', $href);
                $head->appendChild($node);
        }

        /**
        * create a single DOMDocument for all the pages of the book
        * @return DOMDocument
        */
        protected function compile(Book $book) {
                if($book->content) {
                        $content = $book->content;
                        $contentBody = $content->getElementsByTagName('body')->item(0);
                        $firstChapter = 0;
                } elseif(!empty($book->chapters)) {
                        $content = $book->chapters[0]->content;
                        $contentBody = $content->getElementsByTagName('body')->item(0);
                        $firstChapter = 1;
                } else {
                        return getXhtmlFromContent('en', '');
                }
                for($i = $firstChapter; $i < count($book->chapters); $i++) {
                        $this->addChapterInXhtmlBook($book->chapters[$i]->content, $content, $contentBody);
                        foreach($book->chapters[$i]->chapters as $subpage) {
                                $this->addChapterInXhtmlBook($subpage->content, $content, $contentBody);
                        }
                }
                return $content;
        }

        protected function addChapterInXhtmlBook(DOMDocument $chapter, DOMDocument $book, DOMElement $bookBody) {
                $content = $chapter->getElementsByTagName('body')->item(0);
                foreach($content->childNodes as $node) {
                        $node = $book->importNode($node, true);
                        $bookBody->appendChild($node);
                }
                $pageBreak = $book->createElement('div');
                $pageBreak->setAttribute('style', 'page-break-before:always;');
                $bookBody->appendChild($pageBreak);
        }
}

