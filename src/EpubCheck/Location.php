<?php

namespace App\EpubCheck;

class Location {
	private $path;
	private $line = 0;
	private $column = 0;

	/** @var string[] */
	private $contextLines;

	public function __construct( $path, $line, $column, array $contextLines ) {
		$this->path = $path;
		$this->line = $line;
		$this->column = $column;
		$this->contextLines = $contextLines;
	}

	public function __toString(): string {
		return "/$this->path:$this->line:$this->column\n" . implode( "\n", $this->contextLines );
	}
}
