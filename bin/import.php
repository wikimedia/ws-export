#!/usr/bin/php
<?php

require_once dirname( __DIR__ ) . '/bootstrap.php';

use App\CreationLog;

$log = CreationLog::singleton();
$log->import();
