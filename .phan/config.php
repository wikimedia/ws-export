<?php

/**
 * This configuration will be read and overlaid on top of the
 * default configuration. Command-line arguments will be applied
 * after this file is read.
 */
return [
	'target_php_version' => '8.2',

	'directory_list' => [
		'src',
		'vendor',
	],

	'exclude_file_regex' => '@^vendor/.*/(tests?|Tests?)/@',

	'exclude_analysis_directory_list' => [
		'vendor/',
	],

	'suppress_issue_types' => [
		// Done by PHPCS, which can also read inline @var comment
		'PhanUnreferencedUseNormal',
		// Symfony defines multiple classes sometimes, for backwards compatibility.
		'PhanRedefinedExtendedClass',
		'PhanRedefinedClassReference'
	],

	'plugins' => [
		'PregRegexCheckerPlugin',
		'UnusedSuppressionPlugin',
		'DuplicateExpressionPlugin',
		'LoopVariableReusePlugin',
		'RedundantAssignmentPlugin',
		'UnreachableCodePlugin',
		'SimplifyExpressionPlugin',
		'DuplicateArrayKeyPlugin',
		'UseReturnValuePlugin',
		'AddNeverReturnTypePlugin',
	]
];
