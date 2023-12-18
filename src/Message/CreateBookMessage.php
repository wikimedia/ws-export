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

	public function __construct( Book $book, string $filePath, string $format ) {
		$this->book = $book;
		$this->filePath = $filePath;
		$this->format = $format;
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
}
