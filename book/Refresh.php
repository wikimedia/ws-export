<?php
/**
* @author Thomas Pellissier Tanon
* @copyright 2012 Thomas Pellissier Tanon
* @licence http://www.gnu.org/licenses/gpl.html GNU General Public Licence
*/

class Refresh {

        protected $lang = '';
        protected $api = null;

        public function refresh($lang = '') {
                $this->api = new Api($lang);
                $this->lang = $this->api->lang;

                global $wsexportConfig;
                if(@mkdir($wsexportConfig['tempPath'].'/'.$this->lang)) {}

                $this->getI18n();
                $this->getEpubCssWikisource();
                $this->getAboutXhtmlWikisource();
                $this->getNamespacesList();
        }

        protected function getI18n() {
                global $wsexportConfig;
                $ini = parse_ini_file($wsexportConfig['basePath'] . '/book/i18n.ini');
                try {
                        $response = $this->api->get('http://' . $this->lang . '.wikisource.org/w/index.php?title=MediaWiki:Wsexport_i18n.ini&action=raw&ctype=text/plain');
                        $temp = parse_ini_string($response);
                        if($ini != false)
                                $ini = array_merge($ini, $temp);
                } catch(Exception $e) {
                }
                $this->setTempFileContent('i18n.sphp', serialize($ini));
        }

        protected function getEpubCssWikisource() {
                global $wsexportConfig;
                $content = file_get_contents($wsexportConfig['basePath'] . '/book/mediawiki.css');
                try {
                        $content .= "\n" . $this->api->get('http://' . $this->lang . '.wikisource.org/w/index.php?title=MediaWiki:Epub.css&action=raw&ctype=text/css');
                } catch(Exception $e) {
                }
                $this->setTempFileContent('epub.css', $content);
        }

        protected function getAboutXhtmlWikisource() {
                try {
                        $content = $this->api->get('http://' . $this->lang . '.wikisource.org/w/index.php?title=MediaWiki:Wsexport_about&action=render');
                } catch(Exception $e) {
                        try {
                                $content = $this->api->get('http://wikisource.org/w/index.php?title=MediaWiki:Wsexport_about&action=render');
                        } catch(Exception $e) {
                                $content = '';
                        }
                }
                if($content != '') {
                        $i18n = getI18n($this->lang);
                        $content = getXhtmlFromContent($this->lang, $content, $i18n['about']);
                        $document = new DOMDocument('1.0', 'UTF-8');
                        $document->loadXML($content);
                        $parser = new PageParser($document);
                        $content = $parser->getContent();
                        $this->setTempFileContent('about.xhtml', str_replace('href="//', 'href="http://', $document->saveXML()));
                }
        }

        protected function getNamespacesList() {
                $namespaces = array();
                $response = $this->api->query(array('meta' => 'siteinfo', 'siprop' => 'namespaces'));
                foreach($response['query']['namespaces'] as $namespace) {
                        if(isset($namespace['*']) && $namespace['*']) {
                                $namespaces[] = $namespace['*'];
                        }
                        if(isset($namespace['canonical']) && $namespace['canonical']) {
                                $namespaces[] = $namespace['canonical'];
                        }
                }
                $this->setTempFileContent('namespaces.sphp', serialize($namespaces));
        }

        protected function setTempFileContent($name, $content) {
                global $wsexportConfig;
                return file_put_contents($wsexportConfig['tempPath'].'/'.$this->lang.'/'.$name, $content);
        }
}

