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

	/** @var bool */
	private $friendly;

	/**
	 * WsExportException constructor.
	 * @param string $i18nMessage
	 * @param array $i18nParams
	 * @param int $responseCode
	 * @param bool $friendly `true` will mean a 'Learn more' link will be shown with troubleshooting tips.
	 */
	public function __construct( string $i18nMessage, array $i18nParams, int $responseCode, bool $friendly = true ) {
		parent::__construct();
		$this->i18nMessage = $i18nMessage;
		$this->i18nParams = $i18nParams;
		$this->responseCode = $responseCode;
		$this->friendly = $friendly;
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

	/**
	 * Is this a 'friendly' error message ('Learn more' tips should be shown)?
	 * @return bool
	 */
	public function isFriendly(): bool {
		return $this->friendly;
	}
}
