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
                        $this->lang = $this->getHttpLang();
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
        * @var $url the url to the file
        * @return the file content
        */
        public function get($url) {
                $ch = curl_init($url);
                curl_setopt($ch, CURLOPT_USERAGENT, Api::USER_AGENT);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                $response = curl_exec($ch);
                if (curl_errno($ch)) {
                        throw new HttpException(curl_error($ch), curl_errno($ch));
                } else if(curl_getinfo($ch, CURLINFO_HTTP_CODE) >= 400) {
                        throw new HttpException('Not Found', curl_getinfo($ch, CURLINFO_HTTP_CODE));
                }
                curl_close($ch);
                return $response;
        }

        /**
        * @return the lang of the Wikisource used
        */
        public function getHttpLang() {
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
}
