<?php

use TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider;
use TYPO3\CMS\Core\Utility\VersionNumberUtility;

$iconSource = VersionNumberUtility::convertVersionNumberToInteger(VersionNumberUtility::getNumericTypo3Version()) >= 14000000
    ? 'EXT:xima_typo3_mailcatcher/Resources/Public/Icons/module-mailcatcher-v14.svg'
    : 'EXT:xima_typo3_mailcatcher/Resources/Public/Icons/Extension.svg';

return [
    'module-mailcatcher' => [
        'provider' => SvgIconProvider::class,
        'source' => $iconSource,
    ],
];
