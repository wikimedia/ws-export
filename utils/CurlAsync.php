<?php
/**
 * @author Philippe Elie
 * @copyright 2012 Philippe Elie
 * @licence http://www.gnu.org/licenses/gpl.html GNU General Public Licence
 */

/*
 * A class to perform multiple async request calling a callback for each
 * request completed. See buildReponse() for the data format passed
 * to the callback.
 */
class CurlAsync {

        private $mc;
        private $response;
        private $max_request;
        private $pending_request;
        private $current_request_nr;
        private $callbacks;

        /**
         * @var $max_request|int max number of request to perform at a time
         */
        public function __construct($max_request = 8) {
                $this->mc = curl_multi_init();
                $this->response = array();
                $this->max_request = $max_request;
                $this->pending_request_nr = 0;
                $this->pending_request = array();
                $this->callback = array();
        }

        /**
         * @var $url request to execute
         * @return string a key id for this request
         */
        public function addRequest($url, $params, $callback) {
                $ch = Api::getCurl($url);
                if ($params)
                        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
                if ($this->pending_request_nr < $this->max_request) {
                        curl_multi_add_handle($this->mc, $ch);
                        $this->pending_request_nr++;
                } else {
                        $this->pending_request[] = $ch;
                }
                $key = (string)$ch;
                $this->callbacks[$key] = $callback;
                return $key;
        }

        /**
         * @return bool false when iteration over all request is finished.
         */
        public function asyncResult() {
                do {
                        curl_multi_exec($this->mc, $running);
                        $ready = curl_multi_select($this->mc);
                        if ($ready > 0 && $this->getResponse())
                                return true;
                } while ($running > 0);

                return $this->getResponse();
        }

        private function getResponse() {
                while ($done = curl_multi_info_read($this->mc))
                        $this->buildResponse($done);
                if (count($this->response)) {
                        $data = array_shift($this->response);
                        call_user_func($this->callbacks[$data['key']], $data);
                        unset($this->callbacks[$data['key']]);
                        return true;
                }
                return false;
        }

        private function buildResponse($done) {
                $handle = $done['handle'];
                $key = (string)$handle;
                $data['content'] = curl_multi_getcontent($handle);
                $data['http_code'] = curl_getinfo($handle, CURLINFO_HTTP_CODE);
                $data['curl_errno'] = curl_errno($handle);
                $data['curl_result'] = $done['result'];
                $data['key'] = $key;
                $this->response[$key] = $data;

                curl_multi_remove_handle($this->mc, $handle);
                if (count($this->pending_request)) {
                        $ch = array_shift($this->pending_request);
                        curl_multi_add_handle($this->mc, $ch);
                }
        }
}
