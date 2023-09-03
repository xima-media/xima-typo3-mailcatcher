<?php

if (isset($GLOBALS['TYPO3_CONF_VARS']['MAIL']['transport']) && $GLOBALS['TYPO3_CONF_VARS']['MAIL']['transport'] === 'mbox') {
    $iconRegistry = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Imaging\IconRegistry::class);
    $iconRegistry->registerIcon(
        'module-mailcatcher',
        \TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider::class,
        ['source' => 'EXT:xima_typo3_mailcatcher/Resources/Public/Icons/Extension.svg']
    );

    $versionNumberUtility = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Utility\VersionNumberUtility::class);
    $version = $versionNumberUtility->convertVersionStringToArray($versionNumberUtility->getNumericTypo3Version());

    $controllerName = \Xima\XimaTypo3Mailcatcher\Controller\LegacyBackendController::class;
    if ($version['version_main'] >= 11) {
        $controllerName = \Xima\XimaTypo3Mailcatcher\Controller\BackendController::class;
    }

    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
        'XimaTypo3Mailcatcher',
        'system',
        'mails',
        '',
        [
            $controllerName => 'index',
        ],
        [
            'access' => 'admin',
            'iconIdentifier' => 'module-mailcatcher',
            'labels' => 'LLL:EXT:xima_typo3_mailcatcher/Resources/Private/Language/locallang_mod.xlf',
        ]
    );
}
