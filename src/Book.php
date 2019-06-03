<?php

namespace App;

/**
 * @author Thomas Pellissier Tanon
 * @copyright 2011 Thomas Pellissier Tanon
 * @license GPL-2.0-or-later
 */

/**
 * container for all the data on a book
 */
class Book extends Page {

	/**
	 * language of the book like 'en' or 'fr'
	 */
	public $lang = '';

	/**
	 * output options
	 */
	public $options = [];

	/**
	 * meatadata on the book
	 * @see https://wikisource.org/wiki/Wikisource:Microformat
	 */
	public $type = '';
	public $periodical = '';
	public $author = '';
	public $translator = '';
	public $illustrator = '';
	public $school = '';
	public $publisher = '';
	public $year = '';
	public $place = '';
	public $key = '';
	public $progress = '';
	public $volume = '';
	public $scan = '';
	public $cover = '';

	/**
	 * list of the categories as string object like array('1859', 'France')
	 */
	public $categories = [];

	/**
	 * pictures included in the page
	 * @var Picture[]
	 */
	public $pictures = [];

	/**
	 * list of contributors of the book array('PSEUDO' => array('flags' => array(), 'count' => integer))
	 */
	public $credits = [];
}
