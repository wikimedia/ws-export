<?php

namespace App\Tests\Cli;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

/**
 * @covers BookCreator
 */
class BookCliTest extends TestCase {
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
		$binPath = dirname( __DIR__, 2 ) . '/bin/console';
		$outputPath = sys_get_temp_dir();
		$process = new Process( [ 'php', $binPath, 'app:export', '--title', $title, '--lang', $language, '--path', $outputPath, '--nocredits' ] );
		$process->mustRun();
		static::assertTrue( $process->isSuccessful() );
		static::assertFileExists( $outputPath . "/$title.epub" );
	}
}
