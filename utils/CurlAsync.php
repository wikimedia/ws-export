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
        private $callbacks_args;
        private $extern_keys;
        private $current_key;

        /**
         * @var $max_request|int max number of request to perform at a time
         */
        public function __construct($max_request = 16) {
                $this->mc = curl_multi_init();
                $this->response = array();
                $this->max_request = $max_request;
                $this->pending_request_nr = 0;
                $this->pending_request = array();
                $this->callbacks = array();
                $this->callbacks_args = array();
                $this->extern_keys = array();
                $this->current_key = 1;
        }

        /**
         * @var $url request to execute
         * @var $params null|string|array key = value to pass to the query
         * @var $callaback the callback to call on requestion completion
         * @var $callback_args optionnal arg to pass to the callback. Important
         *   parameters passed to the callback are always passed by value.
         * @return string a key id for this request
         */
        public function addRequest($url, $params, $callback, $callback_args = null) {
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
                $extern_key = sprintf("curl_async_%d", $this->current_key++);
                $this->extern_keys[$key] = $extern_key;
                $this->callbacks[$extern_key] = $callback;
                if ($callback_args)
                        $this->callbacks_args[$extern_key] = $callback_args;
                return $extern_key;
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

        /*
         * @var $key key to wait for completion or null to wait the end of
         * all request. Using null is a bottleneck in the application progress,
         * use it only when really needed. Note than passing a key which is
         * already removed from the query queue return immediatly w/o
         * any progress to the other queries. Even if it's simpler to wait
         * for all query keys completion it's better to wait for a set of
         * specific keys than for all key.
         */
        public function waitForKey($key) {
                while (($key === null or isset($this->callbacks[$key])) &&
                       $this->asyncResult()) {
                }
        }

        private function getResponse() {
                while ($done = curl_multi_info_read($this->mc))
                        $this->buildResponse($done);
                if (count($this->response)) {
                        $data = array_shift($this->response);
                        $key = $data['key'];
                        if (isset($this->callbacks_args[$key])) {
                                call_user_func_array($this->callbacks[$key], array_merge(array($data), $this->callbacks_args[$key]));
                                unset($this->callbacks_args[$key]);
                        } else {
                                call_user_func($this->callbacks[$key], $data);
                        }
                        unset($this->callbacks[$key]);
                        return true;
                }
                return false;
        }

        private function buildResponse($done) {
                $handle = $done['handle'];
                $key = $this->extern_keys[(string)$handle];
                $data['content'] = curl_multi_getcontent($handle);
                $data['http_code'] = curl_getinfo($handle, CURLINFO_HTTP_CODE);
                $data['curl_errno'] = curl_errno($handle);
                $data['curl_result'] = $done['result'];
                $data['key'] = $key;
                $this->response[$key] = $data;

                curl_multi_remove_handle($this->mc, $handle);
                curl_close($handle);
                unset($this->extern_keys[(string)$handle]);
                if (count($this->pending_request)) {
                        $ch = array_shift($this->pending_request);
                        curl_multi_add_handle($this->mc, $ch);
                } else {
                        $this->pending_request_nr--;
                }
        }
}
