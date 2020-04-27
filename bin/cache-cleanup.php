#!/usr/bin/php
<?php

require_once dirname( __DIR__ ) . '/bootstrap.php';

\App\FileCache::singleton()->cleanup();
