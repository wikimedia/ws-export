<?php
/**
* @author Thomas Pellissier Tanon
* @copyright 2011 Thomas Pellissier Tanon
* @licence http://www.gnu.org/licenses/gpl.html GNU General Public Licence
*/

/**
* a base class for communications with Wikisource
*/
class Api {
        const USER_AGENT = 'Wikisource Export 0.1';
        public $lang = '';

        /**
        * @var $lang the lang of Wikisource like 'en' or 'fr'
        */
        public function __construct($lang = '') {
                if($lang == '') {
                        $this->lang = Api::getHttpLang();
                } else {
                        $this->lang = $lang;
                }
        }

        /**
        * api query
        * @var $params an associative array for params send to the api
        * @return an array with whe relsult of the api query
        * @throws HttpException
        */
        public function query($params) {
                $data = 'action=query&format=php';
                foreach($params as $param_name => $param_value) {
                        $data .= '&' . $param_name . '=' . urlencode($param_value);
                }
                $url = $this->lang . '.wikisource.org/w/api.php?' . $data;
                $response = $this->get($url);
                return unserialize($response);
        }

        /**
        * @var $title the title of the page
        * @return the content of a page
        */
        public function getPage($title) {
                $url = $this->lang . '.wikisource.org/w/index.php?action=render&title=' . urlencode($title);
                $response = $this->get($url);
                return '<?xml version="1.0" encoding="UTF-8" ?><!DOCTYPE html><html xmlns="http://www.w3.org/1999/xhtml" lang="' . $this->lang . '" xml:lang="' . $this->lang . '"><body>' . $response . '</body></html>';
        }


        /**
        * @var $title array|string the title of the pages
        * @return array|string the content of the pages
        */
        public function getPages($titles) {
                $urls = array();
                foreach($titles as $id => $title) {
                        $urls[$id] = $this->lang . '.wikisource.org/w/index.php?action=render&title=' . urlencode($title);
                }
                $responses = $this->getMulti($urls);
                foreach($responses as $id => $response) {
                        $responses[$id] = '<?xml version="1.0" encoding="UTF-8" ?><!DOCTYPE html><html xmlns="http://www.w3.org/1999/xhtml" lang="' . $this->lang . '" xml:lang="' . $this->lang . '"><body>' . $response . '</body></html>';
                }
                return $responses;
        }

        /**
        * @var $url the url
        * @return the file content
        */
        public function get($url) {
                $ch = $this->getCurl($url);
                $response = curl_exec($ch);
                if(curl_errno($ch)) {
                        throw new HttpException(curl_error($ch), curl_errno($ch));
                } else if(curl_getinfo($ch, CURLINFO_HTTP_CODE) >= 400) {
                        throw new HttpException('Not Found', curl_getinfo($ch, CURLINFO_HTTP_CODE));
                }
                curl_close($ch);
                return $response;
        }

        /**
        * multi requests
        * @var $title array|string the urls
        * @return array|string the content of a pages
        */
        public function getMulti($urls) {
                $mh = curl_multi_init();
                $curl_array = array();
                foreach($urls as $id => $url) {
                        $curl_array[$id] = $this->getCurl($url);
                        curl_multi_add_handle($mh, $curl_array[$id]);
                }
                $running = null;
                do {
                        $status = curl_multi_exec($mh, $running);
                } while ($status === CURLM_CALL_MULTI_PERFORM || $running > 0);

                $res = array();
                foreach($urls as $id => $url) {
                        $res[$id] = curl_multi_getcontent($curl_array[$id]);
                        curl_multi_remove_handle($mh, $curl_array[$id]);
                }
                curl_multi_close($mh);
                return $res;
        }

        /**
        * @var $url the url
        * @return curl
        */
        protected function getCurl($url) {
                $ch = curl_init($url);
                curl_setopt($ch, CURLOPT_USERAGENT, Api::USER_AGENT);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                return $ch;
        }

        /**
        * @return the lang of the Wikisource used
        */
        public static function getHttpLang() {
                $lang = '';
                if(isset($_GET['lang'])) {
                        $lang = $_GET['lang'];
                } else if(isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
                        $langs = explode(',',$_SERVER['HTTP_ACCEPT_LANGUAGE']);
                        if(isset($langs[0])) {
                                $lang = $langs[0];
                        }
                }
                return strtolower(substr($lang, 0, 2));
        }

        /**
        * @return the url encoded like mediawiki does.
        */
        public static function mediawikiUrlEncode($url) {
                $search = array('%21', '%24', '%28', '%29', '%2A', '%2C', '%2D', '%2E', '%2F', '%3A', '%3B', '%40');
                $replace = array('!', '$', '(', ')', '*', ',', '-', '.', '/', ':', ';', '@');
                return str_replace($search, $replace, urlencode($url));
        }
}
