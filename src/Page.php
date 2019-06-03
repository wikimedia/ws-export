<?php

namespace App;

use DOMDocument;

/**
 * @author Thomas Pellissier Tanon
 * @copyright 2011 Thomas Pellissier Tanon
 * @license GPL-2.0-or-later
 */

/**
 * container for a page of Wikisource
 */
class Page {

	/**
	 * title of the book in Wikisource
	 */
	public $title = '';

	/**
	 * name to display
	 */
	public $name = '';

	/**
	 * content of the page
	 * @type DOMDocument
	 */
	public $content = null;

	/**
	 * list of the subpages as Page object
	 */
	public $chapters = [];
}
