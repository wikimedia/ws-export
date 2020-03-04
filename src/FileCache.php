<?php

namespace App;

use DirectoryIterator;
use Exception;

/**
 * Handles this tool's disk cache, maintaining a separate directory and cleaning files from it
 */
class FileCache {
	// Cleanup temporary storage at the end of 5% of requests
	private const CLEANUP_RATE = 5;

	// Delete files after this amount of time in seconds
	private const CACHE_DURATION = 24 * 60 * 60;

	/** @var string Tool-specific temporary directory */
	private $dir;

	/** @var FileCache */
	private static $instance;

	/**
	 * @param string $path Directory for temp files
	 */
	private function __construct( string $path ) {
		$this->dir = $this->makePrivateDirectory( $path );

		// Randomly clean up the cache
		if ( mt_rand( 0, 100 ) <= self::CLEANUP_RATE ) {
			register_shutdown_function( function () {
				$this->cleanup();
			} );
		}
	}

	/**
	 * Returns a global instance of this class
	 * @return FileCache
	 */
	public static function singleton(): FileCache {
		global $wsexportConfig;

		if ( !self::$instance ) {
			self::$instance = new FileCache( $wsexportConfig['tempPath'] );
		}

		return self::$instance;
	}

	/**
	 * @return string Directory for temporary files
	 */
	public function getDirectory(): string {
		return $this->dir;
	}

	/**
	 * Creates a tool-specific temporary directory with specific permissions
	 *
	 * @param string $path
	 * @return string
	 */
	private function makePrivateDirectory( string $path ): string {
		// Use username in the path. The tool needs its own directory so that it knows which
		// files to garbage collect. Can't just use the hardcoded name because
		// wsexport and wsexport-test might end up on the same node, stepping on each other's toes.
		$user = get_current_user();
		// Remove leading "tools." present if running on labs
		$user = preg_replace( '/^tools\./', '', $user );
		$dir = rtrim( $path, '/' ) . '/' . $user;

		// Guard against realpth() returning false sometimes
		$dir = realpath( $dir ) ?: $dir;

		if ( !is_dir( $dir ) ) {
			if ( !mkdir( $dir, 0755 ) ) {
				throw new Exception( "Couldn't create temporary directory $dir" );
			}
		}

		return $dir;
	}

	/**
	 * Cleans up file cache, deleting old files
	 */
	protected function cleanup(): void {
		$di = new DirectoryIterator( $this->dir );
		foreach ( $di as $file ) {
			if ( $file->isFile() && !$file->isDot() ) {
				$this->checkFile( $file->getPathname() );
			}
		}
	}

	/**
	 * Checks whether the given file is present locally and not stale.
	 * Deletes the file if it's stale.
	 * @param string $fileName
	 */
	protected function checkFile( string $fileName ): void {
		// phpcs:disable Generic.PHP.NoSilencedErrors.Discouraged
		$stat = @stat( $fileName );
		// phpcs:enable
		// Failed stat means something went wrong, directories are used for per-wiki metadata
		if ( !$stat || is_dir( $fileName ) || $fileName === '.gitkeep' ) {
			return;
		}
		if ( $stat['mtime'] + self::CACHE_DURATION < time() ) {
			unlink( $fileName );
		}
	}
}
