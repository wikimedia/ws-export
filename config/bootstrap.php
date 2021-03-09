<?php
/**
 * This file is loaded by every entry-point to the application: web, cli, and test.
 * Note that during tests it can at times be loaded more than once,
 * which is why we conditionally load the config here.
 *
 * @TODO Replace this with proper dependency injection.
 *
 * @file
 */

use App\Kernel;

global $wsexportConfig;
if ( !is_array( $wsexportConfig ) ) {
	$kernelTmp = new Kernel( $_SERVER['APP_ENV'], $_SERVER['APP_ENV'] !== 'prod' );
	$kernelTmp->boot();
	$container = $kernelTmp->getContainer();
	$wsexportConfig = [
		'tempPath' => $container->getParameter( 'app.tempPath' ) ?? dirname( __DIR__ ) . '/var/',
	];
}
