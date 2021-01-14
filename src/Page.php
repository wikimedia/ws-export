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
}
