<?php

namespace Xima\XimaTypo3Mailcatcher\Utility;

use PhpMimeMailParser\Parser;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationExtensionNotConfiguredException;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationPathDoesNotExistException;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Xima\XimaTypo3Mailcatcher\Domain\Model\Dto\JsonDateTime;
use Xima\XimaTypo3Mailcatcher\Domain\Model\Dto\MailAttachment;
use Xima\XimaTypo3Mailcatcher\Domain\Model\Dto\MailMessage;

class LogParserUtility
{
    protected string $fileContent = '';

    /**
     * @var array<MailMessage>
     */
    protected array $messages = [];

    protected function loadLogFile(): void
    {
        /** @var ExtensionConfiguration $extensionConfiguration */
        $extensionConfiguration = GeneralUtility::makeInstance(ExtensionConfiguration::class);
        $logPath = $extensionConfiguration->get('xima_typo3_mailcatcher', 'logPath');
        $absolutePath = Environment::getProjectPath() . $logPath;

        if (!file_exists($absolutePath)) {
            return;
        }

        $this->fileContent = (string)file_get_contents($absolutePath);
    }

    /**
     * @throws ExtensionConfigurationExtensionNotConfiguredException
     * @throws ExtensionConfigurationPathDoesNotExistException
     */
    protected function emptyLogFile(): void
    {
        /** @var ExtensionConfiguration $extensionConfiguration */
        $extensionConfiguration = GeneralUtility::makeInstance(ExtensionConfiguration::class);
        $logPath = $extensionConfiguration->get('xima_typo3_mailcatcher', 'logPath');
        $absolutePath = Environment::getProjectPath() . $logPath;

        if (!file_exists($absolutePath)) {
            return;
        }

        //file_put_contents($absolutePath, '');
    }

    protected function extractMessages(): void
    {
        if (!$this->fileContent) {
            return;
        }

        preg_match_all(
            '/(?:; boundary=)(.+)(?:\r\n)/Ums',
            $this->fileContent,
            $boundaries
        );

        // decode whole file
        $this->fileContent = quoted_printable_decode($this->fileContent);

        if (!isset($boundaries[1])) {
            return;
        }

        foreach ($boundaries[1] as $boundary) {
            $separator = '--' . $boundary . '--';
            $messageParts = explode($separator, $this->fileContent);

            if (!str_contains($messageParts[0], 'boundary=')) {
                continue;
            }

            $messageString = $messageParts[0];
            $this->fileContent = $messageParts[1] ?? '';
            $this->messages[] = self::convertToDto((string)$messageString);
        }
    }

    protected function writeMessagesToFile(): void
    {
        foreach ($this->messages as $message) {
            $fileContent = (string)json_encode($message, JSON_THROW_ON_ERROR);
            $fileName = $message->getFileName();
            $filePath = self::getTempPath() . $fileName;
            GeneralUtility::writeFileToTypo3tempDir($filePath, $fileContent);
        }
    }

    protected static function convertToDto(string $msg): MailMessage
    {
        $parser = new Parser();
        $parser->setText($msg);
        $dto = new MailMessage();

        $fromAddresses = $parser->getAddresses('from');
        if (isset($fromAddresses[0])) {
            $dto->fromName = $fromAddresses[0]['display'] ?? '';
            $dto->from = $fromAddresses[0]['address'] ?? '';
        }

        $toAddresses = $parser->getAddresses('to');
        if (isset($toAddresses[0])) {
            $dto->toName = $toAddresses[0]['display'] ?? '';
            $dto->to = $toAddresses[0]['address'] ?? '';
        }

        $headers = $parser->getHeaders();
        $dto->subject = $headers['subject'] ?? '';
        $dto->messageId = md5($headers['message-id'] ?? '');
        try {
            $dto->date = new JsonDateTime($headers['date']);
        } catch (\Exception $e) {
        }

        $dto->bodyPlain = @mb_convert_encoding($parser->getMessageBody('text'), 'UTF-8', 'auto');
        $dto->bodyHtml = @mb_convert_encoding($parser->getMessageBody('html'), 'UTF-8', 'auto');

        $folder = self::getTempPath() . $dto->messageId;
        if (!file_exists($folder)) {
            mkdir($folder);
        }

        $attachments = $parser->getAttachments();

        $folder = self::getTempPath() . $dto->messageId;
        if (count($attachments) && !file_exists($folder)) {
            mkdir($folder);
        }

        foreach ($attachments as $attachment) {
            $attachmentDto = new MailAttachment();

            // get filename from content disposition
            $filename = $attachment->getFilename();
            if (str_starts_with($filename, 'noname')) {
                $headers = $attachment->getHeaders();
                $disposition = $headers['content-disposition'] ?? '';
                preg_match('/(?:; filename )(.+)/', $disposition, $filenameParts);
                $filename = $filenameParts[1];
            }
            $attachmentDto->filename = $filename;

            // calculate public path
            $fullFilePath = $attachment->save($folder, Parser::ATTACHMENT_RANDOM_FILENAME);
            $publicPath = str_replace(Environment::getPublicPath(), '', $fullFilePath);
            $attachmentDto->publicPath = $publicPath;

            // get file size
            $fileSize = filesize($fullFilePath) ?: 0;
            $attachmentDto->filesize = $fileSize;

            $dto->attachments[] = $attachmentDto;
        }

        return $dto;
    }

    public static function getPublicPath(): string
    {
        return '/typo3temp/assets/xima_typo3_mailcatcher/';
    }

    public static function getTempPath(): string
    {
        $tempPath = Environment::getPublicPath() . self::getPublicPath();

        if (!is_dir($tempPath)) {
            mkdir($tempPath);
        }

        return $tempPath;
    }

    public function run(): void
    {
        $this->loadLogFile();
        $this->extractMessages();
        $this->writeMessagesToFile();
        $this->emptyLogFile();
    }

    public function loadMessages(): void
    {
        $messageFiles = array_reverse(array_filter((array)scandir(self::getTempPath()), function ($filename) {
            return strpos((string)$filename, '.json');
        }));

        $this->messages = [];

        foreach ($messageFiles as $filename) {
            if ($message = $this->getMessageByFilename((string)$filename)) {
                $this->messages[] = $message;
            }
        }
    }

    /**
     * @return MailMessage[]
     */
    public function getMessages(): array
    {
        $this->loadMessages();
        return $this->messages;
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

    public function deleteMessageByFilename(string $filename): bool
    {
        $file = self::getTempPath() . '/' . $filename;

        if (!file_exists($file)) {
            return false;
        }

        return unlink($file);
    }

    public function deleteMessages(): bool
    {
        $success = true;

        $messageFiles = array_filter((array)scandir(self::getTempPath()), function ($filename) {
            return strpos((string)$filename, '.json');
        });

        foreach ($messageFiles as $filename) {
            $success = $this->deleteMessageByFilename((string)$filename);
            if (!$success) {
                break;
            }
        }

        return $success;
    }
}
