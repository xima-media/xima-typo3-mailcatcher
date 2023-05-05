<?php

namespace Xima\XimaTypo3Mailcatcher\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Xima\XimaTypo3Mailcatcher\Utility\LogParserUtility;

class AjaxController
{
    public function loadHtmlAction(ServerRequestInterface $request): ResponseInterface
    {
        $params = $request->getQueryParams();

        if (!isset($params['messageFile'])) {
            throw new \InvalidArgumentException('Please provide a message id', 1652881182);
        }

        $parser = GeneralUtility::makeInstance(LogParserUtility::class);
        $mail = $parser->getMessageByFilename($params['messageFile']);

        return new JsonResponse(['src' => $mail->bodyHtml]);
    }

    public function deleteAction(ServerRequestInterface $request): ResponseInterface
    {
        $params = $request->getQueryParams();

        if (!isset($params['messageFile'])) {
            throw new \InvalidArgumentException('Please provide a message id', 1652881182);
        }

        $parser = GeneralUtility::makeInstance(LogParserUtility::class);
        $success = $parser->deleteMessageByFilename($params['messageFile']);

        return new JsonResponse(['success' => $success]);
    }

    public function deleteAllAction(ServerRequestInterface $request): ResponseInterface
    {
        $parser = GeneralUtility::makeInstance(LogParserUtility::class);
        $success = $parser->deleteMessages();

        return new JsonResponse(['success' => $success]);
    }
}
