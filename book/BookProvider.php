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
        protected $curl_async = null;
        protected $options = array(
            'images' => true,
            'fonts' => false,
            'categories' => true
        );
        protected $creditPages = null;
        protected $creditImages = null;
        private $creditUrl = 'http://tools.wmflabs.org/phetools/credits.py';

        /**
        * @var $api Api
        */
        public function __construct(Api $api, $options) {
                $this->api = $api;
                $this->curl_async = new CurlAsync();
                $this->options = array_merge($this->options, $options);
                $this->creditPages = array();
                $this->creditImages = array();
        }

        /**
        * return all the data on a book needed to export it
        * @var $title string the title of the main page of the book in Wikisource
        * @var $isMetadata bool only retrive metadata on the book
        * @return Book
        */
        public function get($title, $isMetadata = false) {
                $title = str_replace(' ', '_', trim($title));
                $doc = $this->getDocument($title);
                return $this->getMetadata($title, $isMetadata, $doc);
        }

        public function getMulti(array $titles, $isMetadata = false) {
                $pages = array();
                foreach($titles as $title) {
                        $page = new Page();
                        $page->title = str_replace(' ', '_', trim($title));
                        $pages[] = $page;
                }

                $pages = $this->getPages($pages);

                foreach($pages as $id => $page) {
                        $pages[$id] = $this->getMetadata($page->title, $isMetadata, $page->content);
                }

                return $pages;
        }

        public function getMetadata($title, $isMetadata, DOMDocument $doc) {
                $page_list = array($title);
                $parser = new PageParser($doc);
                $book = new Book();
                $book->credits_html = '';
                $book->options = $this->options;
                $book->title = $title;
                $book->lang = $this->api->lang;

                $book->metadata_src = $parser->getMetadata('ws-metadata');
                if($book->metadata_src == ''){
                        $book->metadata_src = $title;
                        $metadata_parser = $parser;
                } else {
                        $doc = $this->getDocument($book->metadata_src);
                        $metadata_parser = new PageParser($doc);
                }

                $book->type = $metadata_parser->getMetadata('ws-type');
                $book->name = htmlspecialchars($metadata_parser->getMetadata('ws-title'));
                if($book->name == '') {
                        $book->name = str_replace('_', ' ', $book->metadata_src);
                }
                $book->periodical = htmlspecialchars($metadata_parser->getMetadata('ws-periodical'));
                $book->author = htmlspecialchars($metadata_parser->getMetadata('ws-author'));
                $book->translator = htmlspecialchars($metadata_parser->getMetadata('ws-translator'));
                $book->illustrator = htmlspecialchars($metadata_parser->getMetadata('ws-illustrator'));
                $book->school = htmlspecialchars($metadata_parser->getMetadata('ws-school'));
                $book->publisher = htmlspecialchars($metadata_parser->getMetadata('ws-publisher'));
                $book->year = htmlspecialchars($metadata_parser->getMetadata('ws-year'));
                $book->place = htmlspecialchars($metadata_parser->getMetadata('ws-place'));
                $book->key = $metadata_parser->getMetadata('ws-key');
                $book->progress = $metadata_parser->getMetadata('ws-progress');
                $book->volume = $metadata_parser->getMetadata('ws-volume');
                $book->scan = str_replace(' ', '_', $metadata_parser->getMetadata('ws-scan'));
                $pictures = array();
                if($this->options['images']) {
                        $book->cover = $metadata_parser->getMetadata('ws-cover');
                        if($book->cover != '') {
                                $pictures[$book->cover] = $this->getCover($book->cover, $book->lang);
                                if($pictures[$book->cover]->url == '')
                                        $book->cover = '';
                        }
                }
                if($this->options['categories']) {
                        $book->categories = $this->getCategories($book->metadata_src);
                }
                $pageTitles = $parser->getPagesList();
                $namespaces = $this->getNamespaces();
                if(!$isMetadata) {
                        if(!$parser->metadataIsSet('ws-noinclude')) {
                                $book->content = $parser->getContent();
                                if($this->options['images']) {
                                        $pictures = array_merge($pictures, $parser->getPicturesList());
                                }
                        }
                        $chapterTitles = $parser->getFullChaptersList($title, $page_list, $namespaces);
                        $chapters = $this->getPages($chapterTitles);
                        foreach($chapters as $chapter_key => $chapter) {
                                $parser = new PageParser($chapter->content);
                                if($parser->metadataIsSet('ws-noinclude')) {
                                        unset($chapters[$chapter_key]);
                                        continue;
                                }
                                $pageTitles = array_merge($pageTitles, $parser->getPagesList());
                                $chapter->content = $parser->getContent();
                                if($this->options['images']) {
                                        $pictures = array_merge($pictures, $parser->getPicturesList());
                                }
                                $subpagesTitles = $parser->getChaptersList($chapter, $page_list, $namespaces);
                                if(!empty($subpagesTitles)) {
                                        $subpages = $this->getPages($subpagesTitles);
                                        foreach($subpages as $subpage_key => $subpage) {
                                                $parser = new PageParser($subpage->content);
                                                if($parser->metadataIsSet('ws-noinclude')) {
                                                        unset($chapters[$subpage_key]);
                                                        continue;
                                                }
                                                $pageTitles = array_merge($pageTitles, $parser->getPagesList());
                                                $subpage->content = $parser->getContent();
                                                if($this->options['images']) {
                                                        $pictures = array_merge($pictures, $parser->getPicturesList());
                                                }
                                        }
                                        $chapterTitles = array_merge($chapterTitles, $subpagesTitles);
                                        $chapter->chapters = $subpages;
                                }
                        }
                        $book->chapters = $chapters;

                        $key_credit = $this->startCredit($book, $chapterTitles, $pageTitles);
                        if ($this->options['images'] && !empty($pictures)) {
                                $keyCreditImage = $this->startCreditImage($book, $pictures);
                        }

                        $this->curl_async->waitForKey($key_credit);

                        $pictures = $this->getPicturesData($pictures);
                        if ($this->options['images'] && !empty($pictures)) {
                                $this->curl_async->waitForKey($keyCreditImage);
                        }

                        $book->credits = $this->mergeCredit();

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
        * @var Page[] $the pages
        * @return Page[]
        */
        protected function getPages($pages) {
                $titles = array();
                foreach($pages as $id => $page) {
                        $titles[$id] = $page->title;
                }
                $data = $this->api->getPagesAsync($this->curl_async, $titles);
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
                $data = $this->api->getImagesAsync($this->curl_async, $urls);
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
                $response = $this->api->query(array('titles' => $title, 'prop' => 'categories', 'clshow' => '!hidden'));
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
        public function getCover($cover, $lang) {
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
                        if(strstr($picture->url, '/commons/')) {
                                $picture->url = str_replace('commons/', 'commons/thumb/', $picture->url) . '/page' . $id[1] . '-400px-' . $title . '.jpg';
                        } elseif(strstr($picture->url, '/wikisource/'.$lang)) {
                                $picture->url = str_replace('wikisource/'.$lang, 'wikisource/'.$lang.'/thumb/', $picture->url) . '/page' . $id[1] . '-400px-' . $title . '.jpg';
                        } else {
                                return new Picture();
						}
                        $picture->mimetype = 'image/jpeg';
                        $picture->title .= '.jpg';
                }
                return $picture;
        }

        /**
         * @var $book the Book object
         * @var $chapters an array of Page
         * @var $otherPages an array of string
         * @return a key id for the credit request
         */
        protected function startCredit(Book $book, $chapters, $otherPages) {
                $pages = array( $book->title );
                foreach ($chapters as $id => $chapter)
                        $pages[] = $chapter->title;
                $pages = array_unique(array_merge($pages, $otherPages));
                $pages = join('|', $pages);
                $params = array( 'lang' => $book->lang,
                                 'format' => 'php',
                                 'book' => $book->scan,
                                 'page' => $pages);
                return $this->curl_async->addRequest($this->creditUrl, $params,
                               array($this, 'finishCredit'));
        }

        public function finishCredit($data) {
                if ($data['http_code'] != 200) {
                        //$html = 'Unable to get contributor credits';
                        error_log('getCredit() fail:' .
                                  'http code: ' . $data['http_code'] .
                                  ', curl errno: ' . $data['curl_errno'] .
                                  ', curl_result:' . $data['curl_result']);
                } else {
                        $this->creditPages = unserialize($data['content']);
                }
        }

        /**
         * @var $book the Book object
         * @var $pictures an array of Picture
         * @return a key id for the credit request
         */
        protected function startCreditImage(Book $book, $pictures) {
                $images_set = array( );
                foreach ($pictures as $id => $picture) {
                        if ($picture->name)
                                $images_set[$picture->name] = true;
                }
                $images = array_keys($images_set);
                $images = join('|', $images);
                $params = array( 'lang' => $book->lang,
                                 'format' => 'php',
                                 'image' => $images);
                return $this->curl_async->addRequest($this->creditUrl, $params,
                               array($this, 'finishCreditImage'));
        }

        public function finishCreditImage($data) {
                if ($data['http_code'] != 200) {
                        //$html = 'Unable to get contributor credits';
                        error_log('getCreditImage() fail:' .
                                  'http code: ' . $data['http_code'] .
                                  ', curl errno: ' . $data['curl_errno'] .
                                  ', curl_result:' . $data['curl_result']);
                } else {
                        $this->creditImages = unserialize($data['content']);
                }
        }

        /*
         * merge the credit collected for images and pages and create
         * an html code fragment for these credits
         */
        protected function mergeCredit() {
                $credit = $this->creditPages;
                foreach ($this->creditImages as $name => $values) {
                        if(!isset($credit[$name]))
                                $credit[$name] = array('count' => 0, 'flags' => array());
                        $credit[$name]['count'] += $values['count'];
                        foreach ($values['flags'] as $id => $flag) {
                                if (!inarray($flags, $credit[$name]['flags']))
                                        $credit[$name]['flags'][] = $flag;
                        }
                }
                uasort($credit, "cmp_credit");
                return $credit;
        }

        /**
        * return the list of the namespaces for the current wiki.
        * @return array|string
        */
        public function getNamespaces() {
                $namespaces = unserialize(getTempFile($this->api->lang, 'namespaces.sphp'));
                if(is_array($namespaces)) {
                        return $namespaces;
                } else {
                        return array();
                }
        }
}

/*
 * cmp_credit: compare les crédits de deux utilisateurs
 *
 */
function cmp_credit($a, $b) {
        $f1 = in_array('bot', $a['flags']);
        $f2 = in_array('bot', $b['flags']);
        if ($f1 != $f2)
                return $f1 - $f2;
        return $b['count'] - $a['count'];
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
                $this->removeEnlargeLinks(); //Should be run before getChapterList in order to remove false links
        }

        /**
        * return a metadata in the page
        * @var $id the metadata id like ws-author
        * @return string
        */
        public function getMetadata($id) {
                $node = $this->xPath->query('//*[@id="' . $id . '" or contains(@class, "' . $id . '")]');
                if($node->length != 0) {
                        return $node->item(0)->nodeValue;
                } else {
                        return '';
                }
        }

        /**
        * return if a metadata exist in the page
        * @var $id the metadata id like ws-author
        * @return bool
        */
        public function metadataIsSet($id) {
                $node = $this->xPath->query('//*[@id="' . $id .'" or contains(@class, "' . $id .'")]');
                return $node->length != 0;
        }

        /**
        * return the list of the chapters with the summary if it exist.
        * @return array|Page
        * TODO retrive only main namespace pages ?
        */
        public function getChaptersList($title, $page_list, $namespaces) {
                $list = $this->xPath->query('//*[@id="ws-summary" or contains(@class,"ws-summary")]/descendant::html:a[not(contains(@href,"action=edit") or contains(@class,"extiw") or contains(@class,"external") or contains(@class,"image"))]');
                $chapters = array();
                foreach($list as $link) {
                        $title = str_replace(' ', '_', $link->getAttribute('title'));
                        $parts = explode(':', $title);
                        if($title != '' && !in_array($title, $page_list) && !in_array($parts[0], $namespaces)) {
                            $chapter = new Page();
                            $chapter->title = $title;
                            $chapter->name = $link->nodeValue;
                            $chapters[] = $chapter;
                            $page_list[] = $chapter->title;
                        }
                }
                return $chapters;
        }

        /**
        * return the list of the chapters with the summary if it exist, if not find links to subpages.
        * @return array|Page
        */
        public function getFullChaptersList($title, $page_list, $namespaces) {
                $chapters = $this->getChaptersList($title, $page_list, $namespaces);
                if(empty($chapters)) {
                        $list = $this->xPath->query('//html:a[contains(@href,"' . Api::mediawikiUrlEncode($title) . '") and not(contains(@class,"extiw") or contains(@class,"external") or contains(@href,"#") or contains(@href,"action=edit") or contains(@title,"/Texte entier") or contains(@class,"image"))]');
                        foreach($list as $link) {
                                $title = str_replace(' ', '_', $link->getAttribute('title'));
                                $parts = explode(':', $title);
                                if($title != '' && !in_array($title, $page_list) && !in_array($parts[0], $namespaces)) {
                                        $chapter = new Page();
                                        $chapter->title = $title;
                                        $chapter->name = $link->nodeValue;
                                        $chapters[] = $chapter;
                                        $page_list[] = $chapter->title;
                                }
                        }
                }
                return $chapters;
        }

        /**
        * return the pictures of the file, for all handled <img, the alt
        * attribute is set to the title of the image so the backend can
        * use it to retrieve the src name without relying on the src= attrib.
        * @return array
        */
        public function getPicturesList() {
                $list = $this->xPath->query('//html:a[contains(@class,"image")]');
                $pictures = array();
                foreach($list as $node) {
                        $a = $node->getElementsByTagName('img')->item(0);
                        $picture = new Picture();
                        $url = $a->getAttribute('src');
                        $segments = explode('/', $url);

                        // We need 1st) an unique key for each different image
                        // to index the $pictures array. 2nd) the File: name to
                        // get the credits for the image, this name can't be
                        // used as key because it's not unique, two thumb with
                        // different size of the same image will get the same
                        // File: name. The url ends with 
                        // es/thumb/6/62/PD-icon.svg/50px-PD-icon.svg.png or
                        // es/2/20/Separador.jpg The url can be used as unique
                        // key, so we need only to extract the File:name. This
                        // is kludgy as we need to rely on the path format,
                        // either the 6/62 part is at pos -4/-3 or -3/-2.
                        if (count($segments) >=4 && is_numeric("0x" . $segments[count($segments) - 4]) && is_numeric("0x" . $segments[count($segments) - 3])) {
                                $picture->name = $segments[count($segments) - 2];
                        } else {
                                $picture->name = $segments[count($segments) - 1];
                        }
                        $picture->title = urldecode($url);
                        $picture->url = 'http:' . $url;

                        $pictures[$picture->title] = $picture;
                        $a->setAttribute('alt', $picture->title);
                        $node->parentNode->replaceChild($a, $node);
                }
                $list = $this->xPath->query('//html:a[not(contains(@class,"image"))]/img | //html:img[not(parent::a)]');
                foreach($list as $img) {
                        $picture = new Picture();
                        $url = $img->getAttribute('src');
                        $segments = explode('/', $url);
                        $picture->title = urldecode($segments[count($segments) - 1]);
                        $picture->url = 'http:' . $url;
                        $pictures[$picture->title] = $picture;
                        $img->setAttribute('alt', $picture->title);
                }
                return $pictures;
        }

        /**
        * return the list of the pages of the page namespace included
        * @return array|string
        */
        public function getPagesList() {
                $pages = array();
                $list = $this->xPath->query('//html:*[contains(@class,"ws-pagenum")]');
                foreach($list as $link) {
                        $title = str_replace(' ', '_', $link->getAttribute('title'));
                        if($title) {
                                $pages[] = $title;
                        }
                }
                return $pages;
        }

        /**
        * return the content cleaned : This action must be done after getting metadata that can be in deleted nodes
        * @return DOMDocument The page
        */
        public function getContent() {
                $this->removeNodesWithXpath('//*[contains(@class,"ws-noexport")]');
                $this->removeNodesWithXpath('//html:*[@id="toc"]');
                $this->removeNodesWithXpath('//html:span[@class="editsection" or @class="mw-editsection"]');
                $this->removeNodesWithXpath('//html:a[@class="mw-headline-anchor"]');
                $this->deprecatedNodes('center', 'div', 'text-align:center;');
                $this->deprecatedNodes('strike', 'span', 'text-decoration:line-through;');
                $this->deprecatedNodes('s', 'span', 'text-decoration:line-through;');
                $this->deprecatedNodes('u', 'span', 'text-decoration:underline;');
                $this->deprecatedNodes('font', 'span', '');

                $this->deprecatedAttributes('align', 'text-align');
                $this->deprecatedAttributes('background', 'background-color');
                $this->deprecatedAttributes('bgcolor', 'background-color');
                $this->deprecatedAttributes('border', 'border-width');
                $this->deprecatedAttributes('clear', 'clear');
                $this->deprecatedAttributes('height', 'height');
                $this->deprecatedAttributes('hspace', 'padding');
                $this->deprecatedAttributes('text', 'color');
                $this->deprecatedAttributes('width', 'width');
                $this->deprecatedAttributes('srcset', null);
                $this->deprecatedAttributes('data-file-height', null);
                $this->deprecatedAttributes('data-file-width', null);
                $this->deprecatedAttributes('lang', 'xml:lang', false);

                $this->cleanIds();
                return $this->xPath->document;
        }

        protected function cleanIds() {
                $list = $this->xPath->query('//*[contains(@id,":")]');
                foreach($list as $node) {
                        $node->setAttribute('id', str_replace(':', '_', $node->getAttribute('id')));
                }

                $list = $this->xPath->query('//*[ starts-with(@id,".")]');
                foreach($list as $node) {
                        $node->setAttribute('id', preg_replace('#^\.(.*)$#', '$1', $node->getAttribute('id')));
                }

                $list = $this->xPath->query('//html:span[contains(@class,"pagenum") or contains(@class,"mw-headline")]');
                foreach($list as $node) {
                        $id = $node->getAttribute('id');
                        if (is_numeric($id)) {
                                $node->setAttribute('id', '_' . $id);
                        }
                }
        }

        /**
        * remove links to enlarge pictures
        */
        public function removeEnlargeLinks() {
                $this->removeNodesWithXpath('//html:*[contains(@class,"magnify")]');
        }

        protected function removeNodesWithXpath($query) {
                $nodes = $this->xPath->query($query);
                foreach($nodes as $node) {
                        $node->parentNode->removeChild($node);
                }
        }

        protected function deprecatedNodes($oldName, $newName, $style) {
                $nodes = $this->xPath->query('//html:' . $oldName); //hack: the getElementsByTagName method doesn't catch all tags.
                foreach($nodes as $oldNode) {
                        $newNode = $this->xPath->document->createElement($newName);
                        while($oldNode->firstChild) {
                                $newNode->appendChild($oldNode->firstChild);
                        }
                        foreach($oldNode->attributes as $attribute) {
                                $newNode->setAttribute($attribute->name, $attribute->value);
                        }
                        $newNode->setAttribute('style', $style . ' ' . $newNode->getAttribute('style'));
                        $oldNode->parentNode->replaceChild($newNode, $oldNode);
                }
        }

        protected function deprecatedAttributes($name, $attribute, $isCss = true) {
                $nodes = $this->xPath->query('//html:*[@' . $name . ']');
                foreach($nodes as $node) {
                        if($attribute != null) {
                                if($isCss) {
                                    $node->setAttribute('style', $attribute . ':' . $node->getAttribute($name) . '; ' . $node->getAttribute('style'));
                                } else {
                                    $node->setAttribute($attribute, $node->getAttribute($name));
                                }
                        }
                        $node->removeAttribute($name);
                }
        }
}
