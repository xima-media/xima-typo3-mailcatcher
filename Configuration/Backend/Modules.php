<?php

use Xima\XimaTypo3Mailcatcher\Controller\BackendController;

$isMbox = isset($GLOBALS['TYPO3_CONF_VARS']['MAIL']['transport']) && $GLOBALS['TYPO3_CONF_VARS']['MAIL']['transport'] === 'mbox';

return $isMbox ? [
    'system_mails' => [
        'parent' => 'system',
        'position' => [],
        'access' => 'user,group',
        'iconIdentifier' => 'module-mailcatcher',
        'workspaces' => '*',
        'labels' => 'LLL:EXT:xima_typo3_mailcatcher/Resources/Private/Language/locallang_mod.xlf',
        'extensionName' => 'XimaTypo3Mailcatcher',
        'controllerActions' => [
            BackendController::class => [
                'index',
            ],
        ],
    ],
] : [];
