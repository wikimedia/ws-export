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
                $this->setTempFileContent('i18n', serialize($ini));
        }

        protected function getEpubCssWikisource() {
                try {
                        $content = $this->api->get('http://' . $this->lang . '.wikisource.org/w/index.php?title=MediaWiki:Epub.css&action=raw&ctype=text/css');
                        if($content != '')
                                $this->setTempFileContent('epub.css', $content);
                } catch(Exception $e) {
                }
        }

        protected function getAboutXhtmlWikisource() {
                try {
                        $content = $this->api->get('http://' . $this->lang . '.wikisource.org/w/index.php?title=MediaWiki:Wsexport_about&action=render');
                        if($content != '')
                                $this->setTempFileContent('about.xhtml', str_replace('href="//', 'href="http://', $content));
                } catch(Exception $e) {
                }
        }

        protected function setTempFileContent($name, $content) {
                global $wsexportConfig;
                return file_put_contents($wsexportConfig['tempPath'].'/'.$this->lang.'/'.$name, $content);
        }
}

