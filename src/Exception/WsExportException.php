<?php

namespace App\Exception;

use Exception;

class WsExportException extends Exception {

	/** @var string */
	private $i18nMessage;

	/** @var array<int,string> */
	private $i18nParams;

	/** @var int */
	private $responseCode;

	public function __construct( string $i18nMessage, array $i18nParams, int $reponseCode ) {
		parent::__construct();
		$this->i18nMessage = $i18nMessage;
		$this->i18nParams = $i18nParams;
		$this->responseCode = $reponseCode;
	}

	public function getI18nMessage(): string {
		return 'exception-' . $this->i18nMessage;
	}

	public function getI18nParams(): array {
		return $this->i18nParams;
	}

	public function getResponseCode(): int {
		return $this->responseCode;
	}
}
