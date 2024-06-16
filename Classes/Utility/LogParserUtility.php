<?php

namespace Xima\XimaTypo3Mailcatcher\Utility;

use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Crypto\Random;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Xima\XimaTypo3Mailcatcher\Domain\Model\Dto\JsonDateTime;
use Xima\XimaTypo3Mailcatcher\Domain\Model\Dto\MailAttachment;
use Xima\XimaTypo3Mailcatcher\Domain\Model\Dto\MailMessage;
use ZBateson\MailMimeParser\Header\AddressHeader;
use ZBateson\MailMimeParser\Header\HeaderConsts;
use ZBateson\MailMimeParser\Message;

class LogParserUtility
{
    protected string $fileContent = '';

    /**
     * @var array<MailMessage>
     */
    protected array $messages = [];

    public function run(): void
    {
        $this->loadLogFile();
        $this->extractMessages();
        $this->writeMessagesToFile();
        $this->emptyLogFile();
    }

    public function loadLogFile(): void
    {
        $mboxFile = $GLOBALS['TYPO3_CONF_VARS']['MAIL']['transport_mbox_file'] ?? '';

        if (!file_exists($mboxFile)) {
            return;
        }

        $this->setFileContent((string)file_get_contents($mboxFile));
    }

    public function setFileContent(string $fileContent): void
    {
        $this->fileContent = $fileContent;
    }

    public function extractMessages(): void
    {
        if (!$this->fileContent) {
            return;
        }

        preg_match_all(
            '/(?:; boundary=)(.+)(?:\r\n)/Ums',
            $this->fileContent,
            $boundaries
        );

        if (!isset($boundaries[1])) {
            return;
        }

        foreach ($boundaries[1] as $boundary) {
            $separator = '--' . $boundary . '--';
            $messageParts = explode($separator, $this->fileContent);

            if (!str_contains($messageParts[0], 'boundary=')) {
                continue;
            }

            $messageString = trim($messageParts[0]);
            $this->fileContent = $messageParts[1] ?: '';
            $this->messages[] = self::convertToDto((string)$messageString);
        }
    }

    protected static function convertToDto(string $msg): MailMessage
    {
        $message = Message::from($msg, true);
        $dto = new MailMessage();

        /** @var ?AddressHeader $fromHeader */
        $fromHeader = $message->getHeader(HeaderConsts::FROM);
        if ($fromHeader) {
            $dto->fromName = $fromHeader->getPersonName() ?? '';
            $dto->from = $fromHeader->getEmail() ?? '';
        }

        /** @var ?AddressHeader $toHeader */
        $toHeader = $message->getHeader(HeaderConsts::TO);
        if ($toHeader) {
            $dto->to = $toHeader->getAddresses()[0]->getValue();
            $dto->toName = $toHeader->getAddresses()[0]->getName() ?: '';
        }

        /** @var ?AddressHeader $ccHeader */
        $ccHeader = $message->getHeader(HeaderConsts::CC);
        if ($ccHeader) {
            foreach ($ccHeader->getAddresses() as $address) {
                $dto->ccRecipients[] = [
                    'name' => $address->getName(),
                    'email' => $address->getValue(),
                ];
            }
        }

        /** @var ?AddressHeader $bccHeader */
        $bccHeader = $message->getHeader(HeaderConsts::BCC);
        if ($bccHeader) {
            foreach ($bccHeader->getAddresses() as $address) {
                $dto->bccRecipients[] = [
                    'name' => $address->getName(),
                    'email' => $address->getValue(),
                ];
            }
        }

        $subjectHeader = $message->getHeader(HeaderConsts::SUBJECT);
        if ($subjectHeader) {
            $dto->subject = $subjectHeader->getRawValue();
        }

        $dto->messageId = md5($message->getContentId() ?? '');

        try {
            $dto->date = $message->getHeader('Date') ? new JsonDateTime($message->getHeader('Date')->getRawValue()) : new JsonDateTime();
        } catch (\Exception $e) {
        }

        $dto->bodyPlain = @mb_convert_encoding($message->getTextContent() ?? '', 'UTF-8', 'auto');
        $dto->bodyHtml = @mb_convert_encoding($message->getHtmlContent() ?? '', 'UTF-8', 'auto');

        $folder = self::getTempPath() . $dto->messageId;
        if (!file_exists($folder)) {
            GeneralUtility::mkdir($folder);
        }

        $attachments = $message->getAllAttachmentParts();

        $folder = self::getTempPath() . $dto->messageId;
        if (count($attachments) && !file_exists($folder)) {
            GeneralUtility::mkdir($folder);
        }

        foreach ($attachments as $attachment) {
            $attachmentDto = new MailAttachment();

            // get filename from content disposition
            $filename = $attachment->getFilename();
            $attachmentDto->filename = $filename ?? (GeneralUtility::makeInstance(Random::class))->generateRandomHexString(10);

            // calculate public path
            $fullFilePath = $folder . '/' . $filename;
            $attachment->saveContent($fullFilePath);
            $publicPath = str_replace(Environment::getPublicPath(), '', $fullFilePath);
            $attachmentDto->publicPath = $publicPath;

            // get file size
            $fileSize = filesize($fullFilePath) ?: 0;
            $attachmentDto->filesize = $fileSize;

            $dto->attachments[] = $attachmentDto;

            // replace embedded file identifier with public path
            $contentId = 'cid:' . $attachment->getContentID();
            $dto->bodyHtml = str_replace($contentId, $publicPath, $dto->bodyHtml);
            $dto->bodyPlain = str_replace($contentId, $publicPath, $dto->bodyPlain);
        }

        return $dto;
    }

    public static function getTempPath(): string
    {
        $tempPath = Environment::getPublicPath() . self::getPublicPath();

        if (!is_dir($tempPath)) {
            GeneralUtility::mkdir($tempPath);
        }

        return $tempPath;
    }

    public static function getPublicPath(): string
    {
        return '/typo3temp/assets/xima_typo3_mailcatcher/';
    }

    public function writeMessagesToFile(): void
    {
        foreach ($this->messages as $message) {
            $fileContent = (string)json_encode($message, JSON_THROW_ON_ERROR);
            $fileName = $message->getFileName();
            $filePath = self::getTempPath() . $fileName;
            GeneralUtility::writeFileToTypo3tempDir($filePath, $fileContent);
        }
    }

    public function emptyLogFile(): void
    {
        $mboxFile = $GLOBALS['TYPO3_CONF_VARS']['MAIL']['transport_mbox_file'] ?? '';

        if (!file_exists($mboxFile)) {
            return;
        }

        file_put_contents($mboxFile, '');
    }

    /**
     * @return MailMessage[]
     */
    public function loadAndGetMessages(): array
    {
        $this->loadMessages();
        return $this->messages;
    }

    public function loadMessages(): void
    {
        $messageFiles = GeneralUtility::getFilesInDir(self::getTempPath(), 'json');
        if (!is_array($messageFiles)) {
            return;
        }

        $this->messages = [];

        foreach ($messageFiles as $filename) {
            if ($message = $this->getMessageByFilename((string)$filename)) {
                $this->messages[] = $message;
            }
        }
    }

    public function getMessageByFilename(string $filename): ?MailMessage
    {
        $file = self::getTempPath() . '/' . $filename;

        if (!file_exists($file)) {
            return null;
        }

        $fileContent = file_get_contents(self::getTempPath() . '/' . $filename);
        $data = json_decode((string)$fileContent, true);
        $message = new MailMessage();
        $message->loadFromJson($data);

        return $message;
    }

    /**
     * @return MailMessage[]
     */
    public function getMessages(): array
    {
        return $this->messages;
    }

    public function deleteMessages(): bool
    {
        $success = true;

        $messageFiles = GeneralUtility::getFilesInDir(self::getTempPath(), 'json');
        if (!is_array($messageFiles)) {
            return true;
        }

        foreach ($messageFiles as $filename) {
            $success = $this->deleteMessageByFilename((string)$filename);
            if (!$success) {
                break;
            }
        }

        return $success;
    }

    public function deleteMessageByFilename(string $filename): bool
    {
        $file = self::getTempPath() . '/' . $filename;

        if (!file_exists($file)) {
            return false;
        }

        return unlink($file);
    }
}
