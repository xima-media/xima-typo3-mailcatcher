<?php

$iconRegistry = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Imaging\IconRegistry::class);
$iconRegistry->registerIcon(
    'module-mailcatcher',
    \TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider::class,
    ['source' => 'EXT:xima_typo3_mailcatcher/Resources/Public/Icons/Extension.svg']
);

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
    'XimaTypo3Mailcatcher',
    'system',
    'mails',
    '',
    [
        \Xima\XimaTypo3Mailcatcher\Controller\BackendController::class => 'index',
    ],
    [
        'access' => 'admin',
        'iconIdentifier' => 'module-mailcatcher',
        'labels' => 'LLL:EXT:xima_typo3_mailcatcher/Resources/Private/Language/locallang_mod.xlf',
    ]
);
