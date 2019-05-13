<?php

namespace App;

use App\Cleaner\FileCleaner;
use ZipArchive;

/**
 * @author Thomas Pellissier Tanon
 * @copyright 2011 Thomas Pellissier Tanon
 * @license GPL-2.0-or-later
 */

/**
 * container for a picure included in a page
 */
class Picture {

	/**
	 * title of the picture, this is a sort of uid, different picture
	 * get different title.
	 */
	public $title = '';

	/**
	 * name of the picture, this is identical to the File: name of the
	 * image. Different picture can get the same title (thumb
	 * of different size of the same image).
	 */
	public $name = '';

	/**
	 * url to the picture
	 */
	public $url = '';

	/**
	 * mimetype of the picture
	 */
	public $mimetype = '';

	/**
	 * temporary file with this picture's content
	 * @type string
	 */
	public $tempFile = null;

	public $content;

	/**
	 * Saves this picture's data to a zip archive, sanitizing if needed
	 * If the file does't need sanitization, it's not read to save memory
	 *
	 * @param ZipArchive $zip
	 * @param string $localName
	 */
	public function saveToZip( ZipArchive $zip, string $localName ) {
		if ( FileCleaner::needsCleaning( $this->mimetype ) ) {
			$content = file_get_contents( $this->tempFile );
			$content = FileCleaner::cleanFile( $content, $this->mimetype );
			$zip->addFromString( $localName, $content );
		} else {
			$zip->addFile( $this->tempFile, $localName );
		}
	}

	/**
	 * makes sure that temporary file gets deleted
	 */
	public function __destruct() {
		if ( $this->tempFile && file_exists( $this->tempFile ) ) {
			unlink( $this->tempFile );
		}
	}
}
