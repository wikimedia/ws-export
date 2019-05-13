<?php

namespace App\Tests;

use PHPUnit\Framework\TestCase;

/**
 * @covers BookCreator
 */
class BookTest extends TestCase {
	public function bookProvider() {
		return [
			[ 'The_Kiss_and_its_History', 'en' ],
		];
	}

	/**
	 * @runInSeparateProcess
	 */
	public function testGetEmptyPage() {
		$this->expectOutputRegex( '/' . preg_quote( 'Export tool of Wikisource books in many file formats.' ) . '/' );
		include __DIR__ . '/../../public/book.php';
		$headers = xdebug_get_headers();
		$this->assertContains( 'Content-type: text/html; charset=UTF-8', $headers );
	}

	/**
	 * @dataProvider bookProvider
	 * @runInSeparateProcess
	 * @group integration
	 */
	public function testGetPage( $title, $language ) {
		$_GET['page'] = $title;
		$_GET['lang'] = $language;
		$this->expectOutputRegex( '/^PK/' ); // ZIP header
		include __DIR__ . '/../../public/book.php';
		$headers = xdebug_get_headers();
		$this->assertContains( 'Content-Description: File Transfer', $headers );
		$this->assertContains( 'Content-Type: application/epub+zip', $headers );
		$this->assertContains( 'Content-Disposition: attachment; filename="The_Kiss_and_its_History.epub"', $headers );
	}

	/**
	 * @runInSeparateProcess
	 * @group integration
	 */
	public function testGetNonExistingTitleDisplaysError() {
		$_GET['page'] = 'xxx';
		$this->expectOutputRegex( '/' . preg_quote( 'Page revision not found' ) . '/' );
		include __DIR__ . '/../../public/book.php';
	}

	/**
	 * @runInSeparateProcess
	 */
	public function testGetInvalidFormatDisplaysError() {
		define( 'IN_UNIT_TEST', true );
		$_GET['page'] = 'xxx';
		$_GET['format'] = 'xxx';
		$this->expectOutputRegex( '/' . preg_quote( "The file format 'xxx' is unknown." ) . '/' );
		include __DIR__ . '/../../public/book.php';
	}
}
