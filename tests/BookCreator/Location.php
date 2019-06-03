<?php

namespace App\Tests\BookCreator;

use PHPUnit\Framework\SelfDescribing;

class Location implements SelfDescribing {
	private $path;
	private $line = 0;
	private $column = 0;

	public function __construct( $path, $line, $column ) {
		$this->path = $path;
		$this->line = $line;
		$this->column = $column;
	}

	public function toString(): string {
		return '/' . $this->path . ':' . $this->line . ':' . $this->column;
	}
}
