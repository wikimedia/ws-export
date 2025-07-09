<?php

namespace App;

use App\Repository\CreditRepository;
use App\Util\Api;
use Doctrine\DBAL\Connection;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\UnableToGeneratePublicUrl;

class BookStorage {
	private Connection $connection;
	private FilesystemOperator $filesystem;
	private Api $api;
	private GeneratorSelector $generatorSelector;
	private CreditRepository $creditRepo;
	private FileCache $fileCache;
	private string $localStorageRoot;

	public function __construct(
		Connection $connection,
		Api $api,
		GeneratorSelector $generatorSelector,
		CreditRepository $creditRepo,
		FileCache $fileCache,
		FilesystemOperator $localFilesystem,
		FilesystemOperator $s3Filesystem,
		string $storageFs,
		string $localStorageRoot
	) {
		$this->connection = $connection;
		$this->api = $api;
		$this->generatorSelector = $generatorSelector;
		$this->creditRepo = $creditRepo;
		$this->fileCache = $fileCache;
		$this->filesystem = $storageFs === 's3' ? $s3Filesystem : $localFilesystem;
		$this->localStorageRoot = $localStorageRoot;
	}

	public function getPath( string $lang, string $title, string $format, bool $images, bool $credits = false, string $font = '' ): string {
		return "$lang/$title/"
			. ( $images ? 'images' : 'noimages' )
			. '--' . ( $credits ? 'credits' : 'nocredits' )
			. '--' . ( $font === '' ? 'nofont' : $font )
			. '--' . $format;
	}

	public function get( string $lang, string $title, string $format, bool $images, bool $credits = false, string $font = '' ) {
		$insertSql = 'INSERT INTO books_stored SET lang = ?, title = ?, format = ?, images = ?, credits = ?, font = ?, last_accessed = NOW()'
			. ' ON DUPLICATE KEY UPDATE last_accessed = NOW() ';
		$this->connection->executeStatement( $insertSql, [ $lang, $title, $format, $images ? '1' : '0', $credits ? '1' : '0', $font ] );

		$querySql = 'SELECT * FROM books_stored WHERE lang = ? AND title = ? AND format = ? AND images = ? AND credits = ? AND font = ?';
		$book = $this->connection->executeQuery( $querySql, [ $lang, $title, $format, $images, $credits, $font ] )->fetchAssociative();

		$generator = $this->generatorSelector->getGenerator( $format );
		$book['mime_type'] = $generator->getMimeType();
		$book['filename'] = $title . '.' . $generator->getExtension();

		$storagePath = $this->getPath( $lang, $title, $format, $images, $credits, $font );
		$book['storage_path'] = $storagePath;
		$book['exists'] = $this->filesystem->fileExists( $storagePath );
		if ( $this->filesystem->fileExists( $storagePath ) ) {
			try {
				$book['url'] = $this->filesystem->publicUrl( $storagePath );
			} catch ( UnableToGeneratePublicUrl $ex ) {
				$book['local_path'] = $this->localStorageRoot . $storagePath;
			}
		}

		return $book;
	}

	public function getQueueLength(): int {
		$sql = 'SELECT COUNT(*) FROM books_stored WHERE generated_time IS NULL';
		return (int)$this->connection->executeQuery( $sql, [] )->fetchOne();
	}

	public function getQueue() {
		$sql = 'SELECT * FROM books_stored WHERE generated_time IS NULL';
		return $this->connection->executeQuery( $sql, [] )->fetchAllAssociative();
	}

	/**
	 * Export the given book and store it in the flysystem storage.
	 */
	public function export( string $lang, string $title, string $format, bool $images, bool $credits = false, string $font = '' ) {
		$this->connection->beginTransaction();
		$bookData = [ $lang, $title, $format, $images ? '1' : '0', $credits ? '1' : '0', $font ];

		// Update start_time as an indicator that this job is being processed off the queue.
		$sql = 'UPDATE books_stored SET start_time = NOW() WHERE lang = ? AND title = ? AND format = ? AND images = ? AND credits = ? AND font = ?';
		$this->connection->executeStatement( $sql, $bookData );

		// Export the book to the given format.
		$this->api->setLang( $lang );
		$options = [ 'images' => $images, 'credits' => $credits, 'fonts' => $font ];
		$creator = BookCreator::forApi( $this->api, $format, $options, $this->generatorSelector, $this->creditRepo, $this->fileCache );
		$creator->create( $title );
		$localPath = $creator->getFilePath();

		// Upload the exported book to the flysystem storage.
		$storagePath = $this->getPath( $lang, $title, $format, $images, $credits, $font );
		$stream = fopen( $localPath, 'r' );
		$this->filesystem->writeStream( $storagePath, $stream );
		fclose( $stream );

		// Delete the book from the temp location.
		if ( file_exists( $localPath ) ) {
			unlink( $localPath );
		}

		// Update the generated_time column to indicate that this book is now available from the flysystem storage.
		$sql = 'UPDATE books_stored SET generated_time = NOW() WHERE lang = ? AND title = ? AND format = ? AND images = ? AND credits = ? AND font = ?';
		$this->connection->executeStatement( $sql, $bookData );
		$this->connection->commit();
	}
}
