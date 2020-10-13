<?php

namespace App;

use App\Util\Api;
use Exception;
use Psr\Log\LoggerInterface;

class BookCreator {

	/** @var FontProvider */
	private $fontProvider;

	/** @var Api */
	private $api;

	/** @var LoggerInterface */
	private $logger;

	/** @var Book */
	private $book;

	/** @var string Full filesystem path to the created book. */
	private $filePath;

	/** @var string */
	private $title;

	/** @var string */
	private $outputDir;

	/** @var string */
	private $mimeType;

	/** @var string */
	private $fileExtension = 'epub';

	/** @var string */
	private $format = 'epub';

	/** @var string */
	private $font;

	/** @var bool */
	private $includeImages = true;

	/** @var bool */
	private $includeCredits = true;

	public function __construct( Api $api, LoggerInterface $logger, FontProvider $fontProvider ) {
		$this->api = $api;
		$this->logger = $logger;
		$this->api->setLogger( $this->logger );
		$this->fontProvider = $fontProvider;
	}

	public function getBook(): Book {
		return $this->book;
	}

	public function getTitle(): ?string {
		return $this->title;
	}

	public function setTitle( ?string $title ): void {
		if ( $title ) {
			$this->title = $title;
		}
	}

	public function getFont(): ?string {
		return $this->font;
	}

	public function setFont( ?string $font ): void {
		// Default font for non-latin languages.
		$latinLangs = [ 'fr', 'en', 'de', 'it', 'es', 'pt', 'vec', 'pl', 'nl', 'fa', 'he', 'ar' ];
		if ( !$font && !in_array( $this->getLang(), $latinLangs ) ) {
			$font = 'freeserif';
		}
		$this->font = $font;
	}

	public function getFormat(): ?string {
		return $this->format;
	}

	public function setFormat( ?string $format ): void {
		if ( !$format ) {
			return;
		}
		$this->format = $format;
	}

	public function getIncludeImages(): bool {
		return $this->includeImages;
	}

	public function setIncludeImages( bool $includeImages ): void {
		$this->includeImages = $includeImages;
	}

	public function getIncludeCredits(): bool {
		return $this->includeCredits;
	}

	public function setIncludeCredits( bool $includeCredits ): void {
		$this->includeCredits = $includeCredits;
	}

	public function setLang( ?string $lang ): void {
		if ( !$lang ) {
			return;
		}
		$this->api->setLang( $lang );
	}

	public function getLang(): string {
		return $this->api->getLang();
	}

	public function setOutputDir( string $dir ): void {
		$this->outputDir = $dir;
	}

	public function getMimeType(): string {
		return $this->mimeType;
	}

	public function getFileExtension(): string {
		return $this->fileExtension;
	}

	public function getFilePath(): string {
		return $this->filePath;
	}

	public function setFilePath( string $filePath ) {
		$this->filePath = realpath( $filePath );
	}

	/**
	 * Get a sanitized filename for the created book.
	 * @return string
	 */
	public function getFilename(): string {
		return str_replace( [ '/', '\\' ], '_', trim( $this->book->title ) ) . '.' . $this->getFileExtension();
	}

	public function create(): void {
		date_default_timezone_set( 'UTC' );

		$options = [
			'images' => $this->includeImages,
			'credits' => $this->includeCredits,
		];
		$bookProvider = new BookProvider( $this->api, $options );
		$bookGenerator = GeneratorSelector::select( $this->getFormat(), $this->fontProvider );
		$this->book = $bookProvider->get( $this->getTitle() );
		$this->filePath = $bookGenerator->create( $this->getBook() );
		if ( $this->outputDir ) {
			$this->renameFile( $this->outputDir );
		}
		$this->mimeType = $bookGenerator->getMimeType();
		$this->fileExtension = $bookGenerator->getExtension();
	}

	/**
	 * Move the created book to a new directory.
	 * @param string $dest The destination directory.
	 * @throws Exception If the file could not be renamed.
	 */
	private function renameFile( string $dest ): void {
		if ( !is_dir( $dest ) ) {
			throw new Exception( 'Not a directory: ' . $dest );
		}
		$newFilePath = $dest . '/' . $this->getFilename();
		if ( !is_dir( dirname( $newFilePath ) ) ) {
			mkdir( dirname( $newFilePath ), 0755, true );
		}
		if ( !rename( $this->getFilePath(), $newFilePath ) ) {
			throw new Exception( 'Unable to create output file: ' . $newFilePath );
		}
		$this->filePath = $newFilePath;
	}
}
