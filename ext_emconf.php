<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'XIMA Mail Catcher',
    'description' => 'Display mails that were send to log file',
    'category' => 'backend',
    'author' => 'Maik Schneider',
    'author_email' => 'maik.scheider@xima.de',
    'author_company' => 'XIMA MEDIA GmbH',
    'state' => 'stable',
    'version' => '1.0.0',
    'constraints' => [
        'depends' => [
            'typo3' => '11.0.0-12.99.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
