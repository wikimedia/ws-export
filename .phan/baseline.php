<?php
/**
 * This is an automatically generated baseline for Phan issues.
 * When Phan is invoked with --load-baseline=path/to/baseline.php,
 * The pre-existing issues listed in this file won't be emitted.
 *
 * This file can be updated by invoking Phan with --save-baseline=path/to/baseline.php
 * (can be combined with --load-baseline)
 */
return [
    // # Issue statistics:
    // PhanUndeclaredMethod : 9 occurrences
    // PhanUnreferencedUseNormal : 7 occurrences
    // PhanTypeMismatchArgument : 3 occurrences
    // PhanTypeArraySuspiciousNullable : 2 occurrences
    // PhanTypeMismatchArgumentNullable : 2 occurrences
    // PhanTypeMismatchProperty : 2 occurrences
    // PhanUndeclaredProperty : 2 occurrences
    // PhanCommentParamWithoutRealParam : 1 occurrence
    // PhanTypeInvalidLeftOperandOfNumericOp : 1 occurrence
    // PhanTypeInvalidRightOperandOfNumericOp : 1 occurrence
    // PhanTypeMismatchArgumentNullableInternal : 1 occurrence
    // PhanTypeMismatchDeclaredReturn : 1 occurrence
    // PhanTypeMismatchReturn : 1 occurrence

    // Currently, file_suppressions and directory_suppressions are the only supported suppressions
    'file_suppressions' => [
        'src/BookProvider.php' => ['PhanTypeInvalidLeftOperandOfNumericOp', 'PhanTypeInvalidRightOperandOfNumericOp', 'PhanUndeclaredMethod'],
        'src/Cleaner/BookCleanerEpub.php' => ['PhanUnreferencedUseNormal'],
        'src/Controller/ExportController.php' => ['PhanUnreferencedUseNormal'],
        'src/Controller/StatisticsController.php' => ['PhanUnreferencedUseNormal'],
        'src/Entity/GeneratedBook.php' => ['PhanUnreferencedUseNormal'],
        'src/EpubCheck/Result.php' => ['PhanUndeclaredMethod'],
        'src/FontProvider.php' => ['PhanTypeArraySuspiciousNullable', 'PhanTypeMismatchArgumentNullable', 'PhanTypeMismatchArgumentNullableInternal', 'PhanTypeMismatchDeclaredReturn', 'PhanTypeMismatchProperty', 'PhanTypeMismatchReturn'],
        'src/Generator/ConvertGenerator.php' => ['PhanCommentParamWithoutRealParam'],
        'src/Generator/EpubGenerator.php' => ['PhanTypeMismatchArgument'],
        'src/GeneratorSelector.php' => ['PhanTypeMismatchArgumentNullable'],
        'src/OpdsBuilder.php' => ['PhanUndeclaredProperty'],
        'src/PageParser.php' => ['PhanUnreferencedUseNormal'],
        'src/Repository/GeneratedBookRepository.php' => ['PhanUndeclaredMethod'],
        'src/Util/Api.php' => ['PhanTypeMismatchProperty', 'PhanUndeclaredMethod'],
        'src/Util/Util.php' => ['PhanUnreferencedUseNormal'],
    ],
    // 'directory_suppressions' => ['src/directory_name' => ['PhanIssueName1', 'PhanIssueName2']] can be manually added if needed.
    // (directory_suppressions will currently be ignored by subsequent calls to --save-baseline, but may be preserved in future Phan releases)
];
