#!/usr/bin/php
<?php

require_once dirname( __DIR__ ) . '/bootstrap.php';

use App\CreationLog;

$log = CreationLog::singleton();
if ( $log->createTable() ) {
	echo "Database table installed\n";
}
