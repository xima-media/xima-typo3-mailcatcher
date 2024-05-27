<?php

namespace Xima\XimaTypo3Mailcatcher\Tests\Unit\Utility;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;
use Xima\XimaTypo3Mailcatcher\Utility\LogParserUtility;

class LogParserUtilityTest extends UnitTestCase
{
    protected LogParserUtility $subject;

    public function testRunWithEnvironmentToolMail(): void
    {
        self::assertEmpty($this->subject->loadAndGetMessages());

        $GLOBALS['TYPO3_CONF_VARS']['MAIL']['transport_mbox_file'] = '/var/www/html/Tests/Fixtures/mail-env-tool.log';
        $this->subject->run(false);

        self::assertDirectoryExists($this->subject::getTempPath() . 'd41d8cd98f00b204e9800998ecf8427e');
        self::assertFileExists($this->subject::getTempPath() . '1716817329-74be16979710d4c4e7c6647856088456.json');
        self::assertJson(file_get_contents($this->subject::getTempPath() . '1716817329-74be16979710d4c4e7c6647856088456.json'));

        $messages = $this->subject->loadAndGetMessages();
        self::assertCount(1, $messages);
        self::assertEquals('d41d8cd98f00b204e9800998ecf8427e', $messages[0]->messageId);
        self::assertIsObject($messages[0]->date);
        self::assertStringStartsWith('Test TYPO3 CMS mail delivery from site', $messages[0]->subject);
        self::assertEquals('hello@example.com', $messages[0]->from);
        self::assertEquals('TYPO3 CMS install tool', $messages[0]->fromName);
        self::assertEquals('recipent@example.com', $messages[0]->to);
        self::assertEquals(0, strlen($messages[0]->toName));
        self::assertStringContainsString('Hey TYPO3 Administrator', $messages[0]->bodyPlain);
        self::assertStringContainsString('<table', $messages[0]->bodyHtml);
        self::assertEmpty($messages[0]->ccRecipients);
        self::assertEmpty($messages[0]->bccRecipients);
        self::assertEmpty($messages[0]->attachments);
    }

    public function testDeleteMessages(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['MAIL']['transport_mbox_file'] = '/var/www/html/Tests/Fixtures/mail-env-tool.log';
        $this->subject->run(false);

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
        $this->subject = new LogParserUtility();
    }

    protected function tearDown(): void
    {
        GeneralUtility::rmdir(LogParserUtility::getTempPath(), true);
    }
}
