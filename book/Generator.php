<?php
/**
 * @author Thomas Pellissier Tanon
 * @copyright 2011 Thomas Pellissier Tanon
 * @license GPL-2.0-or-later
 */

/**
 * interface for classes creating file
 */
interface FormatGenerator {

	/**
	 * return the extension of the generated file
	 * @return string
	 */
	public function getExtension();

	/**
	 * return the mimetype of the generated file
	 * @return string
	 */
	public function getMimeType();

	/**
	 * create the file
	 * @param $book Book the title of the main page of the book in Wikisource
	 * @return string path to the created book
	 */
	public function create( Book $book );
}
