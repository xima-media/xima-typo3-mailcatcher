<?php

use TYPO3\CMS\Core\Utility\VersionNumberUtility;
use Xima\XimaTypo3Mailcatcher\Controller\BackendController;

if (isset($GLOBALS['TYPO3_CONF_VARS']['MAIL']['transport']) && $GLOBALS['TYPO3_CONF_VARS']['MAIL']['transport'] === 'mbox') {
    $version = VersionNumberUtility::convertVersionStringToArray(VersionNumberUtility::getNumericTypo3Version());
    $controllerName = BackendController::class;

    if ($version['version_main'] < 12) {
        \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
            'XimaTypo3Mailcatcher',
            'system',
            'mails',
            '',
            [
                $controllerName => 'index',
            ],
            [
                'access' => 'user,group',
                'iconIdentifier' => 'module-mailcatcher',
                'labels' => 'LLL:EXT:xima_typo3_mailcatcher/Resources/Private/Language/locallang_mod.xlf',
            ]
        );
    }
}
