<?php
/**
* @author Thomas Pellissier Tanon
* @copyright 2011 Thomas Pellissier Tanon
* @licence http://www.gnu.org/licenses/gpl.html GNU General Public Licence
*/

/**
* provide all the data needed to create a book file
*/
class BookProvider {
        protected $api = '';

        /**
        * @var $api Api
        */
        public function __construct(Api $api) {
                $this->api = $api;
        }

        /**
        * return all the data on a book needed to export it
        * @var $title the title of the main page of the book in Wikisource
        * @return Book
        * @todo
        */
        public function get($title) {
                $doc = $this->getDocument($title);
                $parser = new PageParser($doc);
                $book = new Book();
                $book->title = $title;
                $book->type = $parser->getMetadata('ws-type');
                $book->name = $parser->getMetadata('ws-title');
                $book->author = $parser->getMetadata('ws-author');
                $book->translator = $parser->getMetadata('ws-translator');
                $book->school = $parser->getMetadata('ws-school');
                $book->publisher = $parser->getMetadata('ws-publisher');
                $book->year = $parser->getMetadata('ws-year');
                $book->place = $parser->getMetadata('ws-place');
                $book->key = $parser->getMetadata('ws-key');
                $book->progress = $parser->getMetadata('ws-progress');
                $book->valume = $parser->getMetadata('ws-volume');
                $book->categories = $parser->getCategories();
                $book->content = $parser->getContent();
                $book->summary = $parser->getSummary();
                if($book->summary != null) {
                        $chapters = $parser->getChaptersList();
                        $book->chapters = $this->getChaptersData($chapters);
                }
                return $book;
        }

        /**
        * return the content of the page
        * @var $title the title of the page in Wikisource
        * @return DOMDocument
        */
        protected function getDocument($title) {
                $content = $this->api->getPage($title);
                $document = new DOMDocument('1.0', 'utf-8');
                $document->loadHTML($content);
                return $document;
        }

        /**
        * return the content of the chapters
        * @var $chapters the list of the chapters
        * @return array
        */
        protected function getChaptersData($chapters) {
                $chapters2 = array();
                foreach($chapters as $chapter) {
                        $doc = $this->getDocument(str_replace(' ', '_', $chapter->title));
                        $parser = new PageParser($doc);
                        $chapter->content = $parser->getContent();
                        $chapters2[] = $chapter;
                }
                return $chapters2;
        }
}


/**
* page parser
*/
class PageParser {
        protected $xPath = null;

        /**
        * @var $doc DOMDocument The page to parse
        */
        public function __construct(DOMNode $doc) {
                $this->xPath = new DOMXPath($doc);
        }

        /**
        * return a metadata in the page
        * @var $id l'identifiant de la métadonnée
        * @return string
        */
        public function getMetadata($id) {
                $node = $this->xPath->query('//*[@id="' . $id .'"]');
                if($node->length != 0) {
                        return $node->item(0)->nodeValue;
                } else {
                        return '';
                }
        }

        /**
        * return the categories in the pages
        * @return array The categories
        */
        public function getCategories() {
                $list = $this->xPath->query('//div[@id="mw-normal-catlinks"]/ul/li/a');
                $categories = array();
                foreach($list as $link) {
                        $cat = explode(':', $link->getAttribute("title"), 2);
                        $categories[] = $cat[1];
                }
                return $categories;
        }

        /**
        * return the summary of the page if he exist, null if not
        * @return DOMElement The summary
        */
        public function getSummary() {
                $list = $this->xPath->query('//*[@id="ws-summary"]');
                if($list->length != 0) {
                        return $list->item(0);
                } else {
                        return null;
                }
        }

        /**
        * return the summary of the page if he exist, null if not
        * @return DOMElement The summary
        */
        public function getChaptersList() {
                $list = $this->xPath->query('//*[@id="ws-summary"]/descendant::a[not(contains(@title,":"))][not(contains(@href,"action=edit"))]');
                $chapters = array();
                foreach($list as $link) {
                        $chapter = new Page();
                        $chapter->title = $link->getAttribute("title");
                        $chapter->name = $link->nodeValue;
                        $chapters[] = $chapter;
                }
                return $chapters;
        }

        /**
        * return the summary of the page if he exist, null if not
        * @return DOMElement The cotent of the page
        * @todo Remove all unused templates
        */
        public function getContent() {
                $list = $this->xPath->query('//div[@id="bodyContent"]/div[@class="mw-content-ltr"] | //div[@id="bodyContent"]/div[@class="mw-content-rtl"]');
                if($list->length != 0) {
                        return $list->item(0);
                } else {
                        throw new HttpException('Not Found', 404);
                }
        }
}
