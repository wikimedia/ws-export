<?php

namespace App\MessageHandler;

use App\BookCreator;
use App\FileCache;
use App\GeneratorSelector;
use App\Message\CreateBookMessage;
use App\Repository\CreditRepository;
use App\Util\Api;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

final class CreateBookMessageHandler implements MessageHandlerInterface {

	/** @var Api */
	private $api;

	/** @var GeneratorSelector */
	private $generatorSelector;

	/** @var CreditRepository */
	private $creditRepo;

	/** @var FileCache */
	private $fileCache;

	public function __construct(
		Api $api,
		GeneratorSelector $generatorSelector,
		CreditRepository $creditRepo,
		FileCache $fileCache
	) {
		$this->api = $api;
		$this->generatorSelector = $generatorSelector;
		$this->creditRepo = $creditRepo;
		$this->fileCache = $fileCache;
	}

	public function __invoke( CreateBookMessage $message ) {
		if ( file_exists( $message->getFilePath() ) ) {
			return;
		}
		$this->api->setLang( $message->getBook()->lang );
		$creator = BookCreator::forApi( $this->api, $message->getFormat(), $message->getBook()->options, $this->generatorSelector, $this->creditRepo, $this->fileCache );
		$creator->create( $message->getBook()->title );
		rename( $creator->getFilePath(), $message->getFilePath() );
	}
}
