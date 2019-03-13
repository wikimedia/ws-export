<?php

/**
 * @covers BookCreator
 */
class BookCliTest extends \PHPUnit\Framework\TestCase {
	public function bookProvider() {
		return [
			[ 'The_Kiss_and_its_History', 'en' ],
			[ 'Les_Fleurs_du_mal', 'fr' ]
		];
	}

	/**
	 * @dataProvider bookProvider
	 * @group integration
	 */
	public function testCreateBookWithCli( $title, $language ) {
		$output = [];
		$returnStatus = 0;
		$book_php = __DIR__ . '/../../cli/book.php';
		$outputPath = sys_get_temp_dir();
		exec( "php $book_php --title $title --lang $language --path $outputPath", $output, $returnStatus );
		$this->assertEquals( $returnStatus, 0, "Conversion to of $title in $language failed:\n" . implode( "\n", $output ) );
	}
}
