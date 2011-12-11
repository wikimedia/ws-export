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
        protected $api = null;
        protected $withPictures = true;

        /**
        * @var $api Api
        */
        public function __construct(Api $api, $withPictures = true) {
                $this->api = $api;
                $tihs->withPictures = $withPictures;
        }

        /**
        * return all the data on a book needed to export it
        * @var $title the title of the main page of the book in Wikisource
        * @return Book
        */
        public function get($title) {
                $doc = $this->getDocument(str_replace(' ', '_', $title));
                $parser = new PageParser($doc);
                $book = new Book();
                $book->uuid = uuid();
                $book->title = $title;
                $book->lang = $this->api->lang;
                $book->type = $parser->getMetadata('ws-type');
                $book->name = $parser->getMetadata('ws-title');
                $book->author = $parser->getMetadata('ws-author');
                $book->translator = $parser->getMetadata('ws-translator');
                $book->illustrator = $parser->getMetadata('ws-illustrator');
                $book->school = $parser->getMetadata('ws-school');
                $book->publisher = $parser->getMetadata('ws-publisher');
                $book->year = $parser->getMetadata('ws-year');
                $book->place = $parser->getMetadata('ws-place');
                $book->key = $parser->getMetadata('ws-key');
                $book->progress = $parser->getMetadata('ws-progress');
                $book->volume = $parser->getMetadata('ws-volume');
                $book->categories = $this->getCategories($title);
                $book->content = $parser->getContent();
                if($this->withPictures) {
                        $pictures = $parser->getPicturesList();
                }
                $chapters = $parser->getChaptersList($title);
                foreach($chapters as $id => $chapter) {
                        $doc = $this->getDocument($chapter->title);
                        $parser = new PageParser($doc);
                        $chapters[$id]->content = $parser->getContent();
                        if($this->withPictures) {
                                $pictures = array_merge($pictures, $parser->getPicturesList());
                        }
                }
                $book->chapters = $chapters;
                $book->pictures = $this->getPicturesData($pictures);
                return $book;
        }

        /**
        * return the content of the page
        * @var $title the title of the page in Wikisource
        * @return DOMDocument
        */
        protected function getDocument($title) {
                $content = $this->api->getPage($title);
                $document = new DOMDocument('1.0', 'UTF-8');
                $document->loadXML($content);
                return $document;
        }

        /**
        * return the content of the pictures
        * @var $pictures the list of the pictures
        * @return array|Picture
        */
        protected function getPicturesData($pictures) {
                foreach($pictures as $id => $picture) {
                        $pictures[$id]->content =  $this->api->get('http:' . $picture->url);
                        $pictures[$id]->mimetype = getMimeType($pictures[$id]->content);
                }
                return $pictures;
        }

        /**
        * return the categories in the pages
        * @var $title the title of the page in Wikisource
        * @return array The categories
        */
        public function getCategories($title) {
                $categories = array();
                $response = $this->api->query(array('titles' => $title, 'prop' => 'categories', '!hidden' => 'true'));
                foreach($response['query']['pages'] as $list) {
                        if(isset($list['categories'])) {
                                foreach($list['categories'] as $categorie) {
                                        $cat = explode(':', $categorie['title'], 2);
                                        $categories[] = $cat[1];
                                }
                        }
                }
                return $categories;
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
                $this->xPath->registerNamespace('html', 'http://www.w3.org/1999/xhtml');
        }

        /**
        * return a metadata in the page
        * @var $id the metadata id like ws-author
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
        * return the list of the chapters with the summary if he exist, if not
        * @return array|Page
        */
        public function getChaptersList($title) {
                $list = $this->xPath->query('//*[@id="ws-summary"]/descendant::html:a[not(contains(@title,":"))][not(contains(@href,"action=edit"))]');
                $chapters = array();
                if($list->length != 0) {
                        foreach($list as $link) {
                                $chapter = new Page();
                                $chapter->title = str_replace(' ', '_', $link->getAttribute('title'));
                                $chapter->name = $link->nodeValue;
                                $chapters[] = $chapter;
                        }
                } else {
                        $list = $this->xPath->query('//html:a[contains(@href,"' . urlencode($title) . '")][not(contains(@href,":"))][not(contains(@href,"action=edit"))]');
                        foreach($list as $link) {
                                $chapter = new Page();
                                $chapter->title = str_replace(' ', '_', $link->getAttribute('title'));
                                $chapter->name = $link->nodeValue;
                                $chapters[] = $chapter;
                        }
                }
                return $chapters;
        }


        /**
        * return the pictures of the file
        * @return array
        */
        public function getPicturesList() {
                $list = $this->xPath->query('//html:a[@class="image"]');
                $pictures = array();
                foreach($list as $node) {
                        $a = $node->getElementsByTagName('img')->item(0);
                        $picture = new Picture();
                        $picture->title = $a->getAttribute('alt');
                        $picture->url = $a->getAttribute('src');
                        $pictures[$picture->title] = $picture;
                        $node->parentNode->replaceChild($a, $node);
                }
                return $pictures;
        }

        /**
        * return the content cleaned : This action must be done after getting metadata that can be in deleted nodes
        * @return DOMDocument The page
        */
        public function getContent() {
                $this->removeNodesWithXpath('//*[contains(@class,"ws-noexport")]');
                $this->removeNodesWithXpath('//html:table[@id="toc"]');
                $this->removeNodesWithXpath('//html:span[@class="editsection"]');
                return $this->xPath->document;
        }

        protected function removeNodesWithXpath($query) {
                $nodes = $this->xPath->query($query);
                foreach($nodes as $node) {
                        $node->parentNode->removeChild($node);
                }
        }
}
