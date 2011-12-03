<?php
/**
* @author Thomas Pellissier Tanon
* @copyright 2011 Thomas Pellissier Tanon
* @licence http://www.gnu.org/licenses/gpl.html GNU General Public Licence
*/

/**
* a class to return an http exception. Use it !
*/
class HttpException extends Exception {

        public function __construct ($message, $code = 0) {
            parent::__construct ($message, $code);
        }

        public function __toString() {
            return $this->message;
        }

        /**
        * @todo
        */
        public function show() {
            if($this->code != 0) {
                header('HTTP/1.0 ' . $this->code . ' ' . $this->message);
                echo $this->message;
            }
        }
}

