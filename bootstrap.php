<?php
/**
 * This file is loaded by every entry-point to the application: web, cli, and test.
 * Note that during tests it can at times be loaded more than once,
 * which is why we conditionally load the config here.
 *
 * @file
 */

require_once __DIR__ . '/vendor/autoload.php';

global $wsexportConfig;
if ( !is_array( $wsexportConfig ) ) {
	// Use 'require' rather than 'require_once' so that we can re-read config.php when required.
	$wsexportConfig = require __DIR__ . '/config.php';
}
