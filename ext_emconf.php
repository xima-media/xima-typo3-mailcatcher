<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'XIMA Mail Catcher',
    'description' => 'Backend module to display mails that were send to log file',
    'category' => 'backend',
    'author' => 'Maik Schneider',
    'author_email' => 'maik.scheider@xima.de',
    'author_company' => 'XIMA MEDIA GmbH',
    'state' => 'stable',
    'version' => '1.4.0',
    'constraints' => [
        'depends' => [
            'typo3' => '11.0.0-13.99.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
    'autoload' => [
        'psr-4' => [
            'Xima\\XimaTypo3Mailcatcher\\' => 'Classes',
        ],
    ],
];
