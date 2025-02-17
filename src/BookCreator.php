<?php

namespace App;

use App\Exception\WsExportException;
use App\Generator\FormatGenerator;
use App\Repository\CreditRepository;
use App\Util\Api;
use Exception;

/**
 * @license GPL-2.0-or-later
 */
class BookCreator {
	private $bookProvider;
	private $bookGenerator;

	/** @var Book */
	private $book;

	/** @var string Full filesystem path to the created book. */
	private $filePath;

	public static function forApi( Api $api, $format, GeneratorSelector $generatorSelector, CreditRepository $creditRepo, FileCache $fileCache ) {
		if ( !in_array( $format, GeneratorSelector::getAllFormats() ) ) {
			$list = '"' . implode( '", "', GeneratorSelector::getValidFormats() ) . '"';
			throw new WsExportException( 'invalid-format', [ $format, $list ], 400 );
		}

		return new BookCreator(
			new BookProvider( $api, $creditRepo, $fileCache ),
			$generatorSelector->getGenerator( $format )
		);
	}

	public function __construct( BookProvider $bookProvider, FormatGenerator $bookGenerator ) {
		$this->bookProvider = $bookProvider;
		$this->bookGenerator = $bookGenerator;
	}

	/**
	 * Create the book.
	 * @param string $title
	 * @param string|null $outputPath
	 */
	public function create( $title, array $options, $outputPath = null ): void {
		date_default_timezone_set( 'UTC' );

		$this->book = $this->bookProvider->get( $title, $options );
		$this->filePath = $this->bookGenerator->create( $this->book );
		if ( $outputPath ) {
			$this->renameFile( $outputPath );
		}
	}

	public function getBook(): Book {
		return $this->book;
	}

	public function getMimeType() {
		return $this->bookGenerator->getMimeType();
	}

	public function getExtension() {
		return $this->bookGenerator->getExtension();
	}

	public function getFilePath(): string {
		return $this->filePath;
	}

	/**
	 * Get a sanitized filename for the created book.
	 * @return string
	 */
	public function getFilename(): string {
		return str_replace( [ '/', '\\' ], '_', trim( $this->book->title ) ) . '.' . $this->getExtension();
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
