<?php

namespace App;

use DOMDocument;

/**
 * Container for a page of Wikisource.
 */
class Page {

	/** @var string Wiki page name of the page on Wikisource. */
	public $title = '';

	/** @var string The page's actual title, e.g. a page with title 'Foo/Bar' might have a name of 'Foo, Bar'. */
	public $name = '';

	/** @var DOMDocument Content of the page. */
	public $content = null;

	/** @var Page[] List of the subpages. */
	public $chapters = [];

	/**
	 * Convenience method to create a new page with a name and title.
	 * @param string $name
	 * @param string $title
	 * @return Page
	 */
	public static function factory( string $name, string $title ) {
		$page = new self();
		$page->title = $title;
		$page->name = $name;
		return $page;
	}
}
