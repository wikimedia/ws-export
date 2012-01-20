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
        * @var $title string the title of the main page of the book in Wikisource
        * @var $isMetadata bool only retrive metadata on the book
        * @return Book
        */
        public function get($title, $isMetadata = false) {
                $title = str_replace(' ', '_', $title);
                $doc = $this->getDocument($title);
                $parser = new PageParser($doc);
                $book = new Book();
                $book->title = $title;
                $book->lang = $this->api->lang;
                $book->type = $parser->getMetadata('ws-type');
                $book->name = htmlspecialchars($parser->getMetadata('ws-title'));
                $book->author = htmlspecialchars($parser->getMetadata('ws-author'));
                $book->translator = htmlspecialchars($parser->getMetadata('ws-translator'));
                $book->illustrator = htmlspecialchars($parser->getMetadata('ws-illustrator'));
                $book->school = htmlspecialchars($parser->getMetadata('ws-school'));
                $book->publisher = htmlspecialchars($parser->getMetadata('ws-publisher'));
                $book->year = htmlspecialchars($parser->getMetadata('ws-year'));
                $book->place = htmlspecialchars($parser->getMetadata('ws-place'));
                $book->key = $parser->getMetadata('ws-key');
                $book->progress = $parser->getMetadata('ws-progress');
                $book->volume = $parser->getMetadata('ws-volume');
                $book->scan = str_replace(' ', '_', $parser->getMetadata('ws-scan'));
                $pictures = array();
                if($this->withPictures) {
                        $book->cover = $parser->getMetadata('ws-cover');
                        if($book->cover != '') {
                                $pictures[$book->cover] = $this->getCover($book->cover);
                                if($pictures[$book->cover]->url == '')
                                        $book->cover = '';
                        }
                }
                $book->categories = $this->getCategories($title);
                if(!$isMetadata) {
                        $book->content = $parser->getContent();
                        if($this->withPictures) {
                                $pictures = array_merge($pictures, $parser->getPicturesList());
                        }
                        $chapters = $parser->getChaptersList($title);
                        $chapters = $this->getPages($chapters);
                        foreach($chapters as $chapter) {
                                $parser = new PageParser($chapter->content);
                                $chapter->content = $parser->getContent();
                                if($this->withPictures) {
                                        $pictures = array_merge($pictures, $parser->getPicturesList());
                                }
                        }
                        $book->chapters = $chapters;
                        $pictures = $this->getPicturesData($pictures);
                }
                $book->pictures = $pictures;
                return $book;
        }

        /**
        * return the content of the page
        * @var $title string the title of the page in Wikisource
        * @return DOMDocument
        */
        protected function getDocument($title) {
                $content = $this->api->getPage($title);
                $document = new DOMDocument('1.0', 'UTF-8');
                $document->loadXML($content);
                return $document;
        }

        /**
        * return the content of the page
        * @var $title array|Page the pages
        * @return array|Page
        */
        protected function getPages($pages) {
                $titles = array();
                foreach($pages as $id => $page) {
                        $titles[$id] = $page->title;
                }
                $data = $this->api->getPages($titles);
                foreach($pages as $id => $page) {
                        $document = new DOMDocument('1.0', 'UTF-8');
                        $document->loadXML($data[$id]);
                        $page->content = $document;
                }
                return $pages;
        }

        /**
        * return the content of the pictures
        * @var $pictures array|Picture the list of the pictures
        * @return array|Picture
        */
        protected function getPicturesData($pictures) {
                $urls = array();
                foreach($pictures as $id => $picture) {
                        $urls[$id] = $picture->url;
                }
                $data = $this->api->getMulti($urls);
                foreach($pictures as $id => $picture) {
                        $picture->content = $data[$id];
                        $picture->mimetype = getMimeType($picture->content);
                }
                return $pictures;
        }

        /**
        * return the categories in the pages
        * @var $title string the title of the page in Wikisource
        * @return array|string The categories
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

        /**
        * return the cover of the book
        * @var $cover string the name of the cover
        * @return Picture The cover
        */
        public function getCover($cover) {
                $id = explode('/', $cover);
                $title = $id[0];
                $picture = new Picture();
                $picture->title = $cover;
                $response = $this->api->query(array('titles' => 'File:' . $title, 'prop' => 'imageinfo', 'iiprop' => 'mime|url'));
                $page = end($response['query']['pages']);
                $picture->url = $page['imageinfo'][0]['url'];
                $picture->mimetype = $page['imageinfo'][0]['mime'];
                if(in_array($picture->mimetype, array('image/vnd.djvu', 'application/pdf'))) {
                        if(!isset($id[1]))
                                return new Picture();
                        $temps = explode('/', $picture->url);
                        foreach($temps as $temp) {
                                $title = $temp;
                        }
                        $picture->url = str_replace('commons/', 'commons/thumb/', $picture->url) . '/page' . $id[1] . '-400px-' . $title . '.jpg';
                        $picture->mimetype = 'image/jpeg';
                        $picture->title .= '.jpg';
                }
                return $picture;
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
        public function __construct(DOMDocument $doc) {
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
                        $list = $this->xPath->query('//html:a[contains(@href,"wikisource.org")][contains(@href,"' . Api::mediawikiUrlEncode($title) . '")][not(contains(@href,"#"))][not(contains(@href,":"))][not(contains(@href,"action=edit"))][not(contains(@title,"/Texte entier"))]');
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
                        $picture->url = 'http:' . $a->getAttribute('src');
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
                $this->deprecatedNodes('center', 'div', 'text-align:center;');
                $this->deprecatedNodes('strike', 'span', 'text-decoration:line-through;');
                $this->deprecatedNodes('s', 'span', 'text-decoration:line-through;');
                $this->deprecatedNodes('u', 'span', 'text-decoration:underline;');
                $this->deprecatedNodes('font', 'span', '');
                return $this->xPath->document;
        }

        protected function removeNodesWithXpath($query) {
                $nodes = $this->xPath->query($query);
                foreach($nodes as $node) {
                        $node->parentNode->removeChild($node);
                }
        }

        protected function deprecatedNodes($oldName, $newName, $style) {
                $nodes = $this->xPath->query('//html:' . $oldName); //The getElementsByTagName doesn't find all tags
                foreach($nodes as $oldNode) {
                        $newNode = $this->xPath->document->createElement($newName);
                        foreach($oldNode->childNodes as $child){
                                $newNode->appendChild($child);
                        }
                        foreach($oldNode->attributes as $attrName => $attrNode) {
                                $newNode->setAttribute($attrName, $attrNode);
                        }
                        $newNode->setAttribute('style', $style . ' ' . $newNode->getAttribute('style'));
                        $oldNode->parentNode->replaceChild($newNode, $oldNode);
                }
        }
}
