<?php

namespace Xima\XimaTypo3Mailcatcher\Tests\Unit\Utility;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;
use Xima\XimaTypo3Mailcatcher\Utility\LogParserUtility;

class LogParserUtilityTest extends UnitTestCase
{
    protected LogParserUtility $subject;

    public static function mailDataProvider(): array
    {
        return [
            [
                'mail-env-tool.log',
                'd41d8cd98f00b204e9800998ecf8427e',
                '1716817329-74be16979710d4c4e7c6647856088456.json',
                'Test TYPO3 CMS mail delivery from site "New TYPO3 site"',
                'hello@example.com',
                'TYPO3 CMS install tool',
                'recipent@example.com',
                '',
            ],
        ];
    }

    /**
     * @dataProvider mailDataProvider
     */
    public function testFileCreation(
        $transportMboxFile,
        $expectedMessageId,
        $expectedMessageFileName,
    ): void {
        $this->subject->setFileContent(file_get_contents(__DIR__ . '/../../Fixtures/' . $transportMboxFile));
        $this->subject->loadLogFile();
        $this->subject->extractMessages();
        $this->subject->writeMessagesToFile();

        self::assertDirectoryExists($this->subject::getTempPath() . $expectedMessageId);
        self::assertFileExists($this->subject::getTempPath() . $expectedMessageFileName);
        self::assertJson(file_get_contents($this->subject::getTempPath() . $expectedMessageFileName));
    }

    public function testEmptyLogFile(): void
    {
        $this->subject->setFileContent(file_get_contents(__DIR__ . '/../../Fixtures/empty.log'));
        $this->subject->loadLogFile();
        $this->subject->extractMessages();
        $this->subject->writeMessagesToFile();

        self::assertEmpty(GeneralUtility::get_dirs(($this->subject::getTempPath())));
        self::assertEmpty($this->subject->getMessages());
    }

    /**
     * @dataProvider mailDataProvider
     */
    public function testMailContent(
        $transportMboxFile,
        $expectedMessageId,
        $expectedMessageFileName,
        $expectedSubject,
        $expectedFrom,
        $expectedFromName,
        $expectedTo,
        $expectedToName,
    ): void {
        $this->subject->setFileContent(file_get_contents(__DIR__ . '/../../Fixtures/' . $transportMboxFile));
        $this->subject->extractMessages();

        $messages = $this->subject->getMessages();
        self::assertCount(1, $messages);

        self::assertEquals($expectedMessageId, $messages[0]->messageId);
        self::assertIsObject($messages[0]->date);
        self::assertEquals($expectedSubject, $messages[0]->subject);
        self::assertEquals($expectedFrom, $messages[0]->from);
        self::assertEquals($expectedFromName, $messages[0]->fromName);
        self::assertEquals($expectedTo, $messages[0]->to);
        self::assertEquals($expectedToName, $messages[0]->toName);
        self::assertEmpty($messages[0]->ccRecipients);
        self::assertEmpty($messages[0]->bccRecipients);
        self::assertEmpty($messages[0]->attachments);
    }

    /**
     * @dataProvider mailDataProvider
     */
    public function testDeleteMessages($transportMboxFile): void
    {
        $this->subject->setFileContent(file_get_contents(__DIR__ . '/../../Fixtures/' . $transportMboxFile));
        $this->subject->loadLogFile();
        $this->subject->extractMessages();
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
    }
}
