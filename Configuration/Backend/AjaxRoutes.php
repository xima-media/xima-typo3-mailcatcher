<?php

use Xima\XimaTypo3Mailcatcher\Controller\AjaxController;

return [
    'mailcatcher_html' => [
        'path' => '/mailcatcher/html',
        'target' => AjaxController::class . '::loadHtmlAction',
    ],
    'mailcatcher_delete' => [
        'path' => '/mailcatcher/delete',
        'target' => AjaxController::class . '::deleteAction',
    ],
    'mailcatcher_delete_all' => [
        'path' => '/mailcatcher/delete/all',
        'target' => AjaxController::class . '::deleteAllAction',
    ],
];
