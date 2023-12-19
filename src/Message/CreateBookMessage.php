<?php

namespace App\Message;

use App\Book;

final class CreateBookMessage {

	/** @var Book */
	private $book;

	/** @var string */
	private $filePath;

	/** @var string */
	private $format;

	/** @var int Timestamp that this message should expire. */
	private $expiry;

	public function __construct( Book $book, string $filePath, string $format, int $expiry ) {
		$this->book = $book;
		$this->filePath = $filePath;
		$this->format = $format;
		$this->expiry = $expiry;
	}

	public function getBook(): Book {
		return $this->book;
	}

	public function getFilePath(): string {
		return $this->filePath;
	}

	public function getFormat(): string {
		return $this->format;
	}

	public function getExpiry(): int {
		return $this->expiry;
	}
}
