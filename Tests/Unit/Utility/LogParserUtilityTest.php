<?php

namespace Xima\XimaTypo3Mailcatcher\Tests\Unit\Utility;

use JsonException;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;
use Xima\XimaTypo3Mailcatcher\Utility\LogParserUtility;

class LogParserUtilityTest extends UnitTestCase
{
    protected LogParserUtility $subject;

    /**
     * @return array<int, array<string, string>>[]
     */
    public static function mailDataProvider(): array
    {
        return [
            [
                [
                    'transportMboxFile' => 'mail-env-tool.log',
                    'expectedMessageId' => 'd41d8cd98f00b204e9800998ecf8427e',
                    'expectedMessageFileName' => '1716817329-74be16979710d4c4e7c6647856088456.json',
                    'expectedSubject' => 'Test TYPO3 CMS mail delivery from site "New TYPO3 site"',
                    'expectedFrom' => 'hello@example.com',
                    'expectedFromName' => 'TYPO3 CMS install tool',
                    'expectedTo' => 'recipent@example.com',
                    'expectedToName' => '',
                ],
            ],
        ];
    }

    /**
     * @dataProvider mailDataProvider
     * @param array<string, string> $exampleMail
     * @throws JsonException
     */
    public function testFileCreation(array $exampleMail): void
    {
        $this->subject->setFileContent(file_get_contents(__DIR__ . '/../../Fixtures/' . $exampleMail['transportMboxFile']) ?: '');
        $this->subject->loadLogFile();
        $this->subject->extractMessages();
        $this->subject->writeMessagesToFile();

        self::assertDirectoryExists($this->subject::getTempPath() . $exampleMail['expectedMessageId']);
        self::assertFileExists($this->subject::getTempPath() . $exampleMail['expectedMessageFileName']);
        self::assertJson(file_get_contents($this->subject::getTempPath() . $exampleMail['expectedMessageFileName']) ?: '');
        self::assertCount(1, $this->subject->loadAndGetMessages());
    }

    public function testNonExistingLogFile(): void
    {
        $this->subject->loadLogFile();
        self::assertEmpty($this->subject->getMessages());
    }

    public function testEmptyLogFile(): void
    {
        $this->subject->setFileContent(file_get_contents(__DIR__ . '/../../Fixtures/empty.log') ?: '');
        $this->subject->loadLogFile();
        $this->subject->extractMessages();
        $this->subject->writeMessagesToFile();

        self::assertEmpty(GeneralUtility::get_dirs(($this->subject::getTempPath())));
        self::assertEmpty($this->subject->getMessages());
    }

    public function testEmptyingLogFile(): void
    {
        $exampleFilePath = __DIR__ . '/../../Fixtures/example.log';
        file_put_contents($exampleFilePath, 'Example data');
        $GLOBALS['TYPO3_CONF_VARS']['MAIL']['transport_mbox_file'] = $exampleFilePath;

        $this->subject->emptyLogFile();

        self::assertEquals('', file_get_contents($exampleFilePath));
    }

    public function testEmptyingMissingLogFile(): void
    {
        $missingFile = __DIR__ . '/../../Fixtures/missing.log';
        self::assertFileDoesNotExist($missingFile);
        $GLOBALS['TYPO3_CONF_VARS']['MAIL']['transport_mbox_file'] = $missingFile;
        $this->subject->emptyLogFile();
    }

    /**
     * @dataProvider mailDataProvider
     * @param array<string, string> $exampleMail
     */
    public function testMailContent(array $exampleMail): void
    {
        $this->subject->setFileContent(file_get_contents(__DIR__ . '/../../Fixtures/' . $exampleMail['transportMboxFile']) ?: '');
        $this->subject->extractMessages();

        $messages = $this->subject->getMessages();
        self::assertCount(1, $messages);

        self::assertEquals($exampleMail['expectedMessageId'], $messages[0]->messageId);
        self::assertIsObject($messages[0]->date);
        self::assertEquals($exampleMail['expectedSubject'], $messages[0]->subject);
        self::assertEquals($exampleMail['expectedFrom'], $messages[0]->from);
        self::assertEquals($exampleMail['expectedFromName'], $messages[0]->fromName);
        self::assertEquals($exampleMail['expectedTo'], $messages[0]->to);
        self::assertEquals($exampleMail['expectedToName'], $messages[0]->toName);
        self::assertEmpty($messages[0]->ccRecipients);
        self::assertEmpty($messages[0]->bccRecipients);
        self::assertEmpty($messages[0]->attachments);
    }

    /**
     * @dataProvider mailDataProvider
     * @param array<string, string> $exampleMail
     * @throws JsonException
     */
    public function testDeleteMessages(array $exampleMail): void
    {
        $this->subject->setFileContent(file_get_contents(__DIR__ . '/../../Fixtures/' . $exampleMail['transportMboxFile']) ?: '');
        $this->subject->extractMessages();
        $this->subject->writeMessagesToFile();

        self::assertCount(1, $this->subject->loadAndGetMessages());

        $this->subject->deleteMessages();
        self::assertEmpty($this->subject->loadAndGetMessages());
    }

    public function testGetTempPath(): void
    {
        self::assertGreaterThan(2, strlen($this->subject::getTempPath()));
        self::assertDirectoryExists($this->subject::getTempPath());
    }

    protected function setUp(): void
    {
        parent::setUp();
        GeneralUtility::rmdir(LogParserUtility::getTempPath(), true);
        $this->subject = new LogParserUtility();
    }

    protected function tearDown(): void
    {
        GeneralUtility::rmdir(LogParserUtility::getTempPath(), true);
        GeneralUtility::rmdir(__DIR__ . '/../../Fixtures/example.log', true);
    }
}
