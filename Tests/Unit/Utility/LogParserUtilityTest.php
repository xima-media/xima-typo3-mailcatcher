<?php

namespace Xima\XimaTypo3Mailcatcher\Tests\Unit\Utility;

use JsonException;
use PHPUnit\Framework\Attributes\DataProvider;
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
     * @param array<string, string> $exampleMail
     * @throws JsonException
     */
    #[DataProvider('mailDataProvider')]
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
     * @param array<string, string> $exampleMail
     */
    #[DataProvider('mailDataProvider')]
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
     * @param array<string, string> $exampleMail
     * @throws JsonException
     */
    #[DataProvider('mailDataProvider')]
    public function testDeleteMessages(array $exampleMail): void
    {
        $this->subject->setFileContent(file_get_contents(__DIR__ . '/../../Fixtures/' . $exampleMail['transportMboxFile']) ?: '');
        $this->subject->extractMessages();
        $this->subject->writeMessagesToFile();

        self::assertCount(1, $this->subject->loadAndGetMessages());

        $this->subject->deleteMessages();
        self::assertEmpty($this->subject->loadAndGetMessages());
    }

    /**
     * @return array<string, array{0: string, 1: array<int, array{subject: string, from: string, to: string, attachments: int}>}>
     */
    public static function multipleMailsDataProvider(): array
    {
        return [
            'single mail' => [
                'Fixtures/mail-env-tool.log',
                [
                    [
                        'subject' => 'Test TYPO3 CMS mail delivery from site "New TYPO3 site"',
                        'from' => 'hello@example.com',
                        'to' => 'recipent@example.com',
                        'attachments' => 0,
                    ],
                ],
            ],
            'mails with headers in different orders' => [
                'Fixtures/mail-multiple-header-orders.log',
                [
                    [
                        'subject' => 'Test1: to/from/subject',
                        'from' => 'from1@example.com',
                        'to' => 'to1@example.org',
                        'attachments' => 0,
                    ],
                    [
                        'subject' => 'Test2: to/subject/from',
                        'from' => 'from2@example.com',
                        'to' => 'to2@example.org',
                        'attachments' => 1,
                    ],
                    [
                        'subject' => 'Test3: subject/from/to',
                        'from' => 'from3@example.com',
                        'to' => 'to3@example.org',
                        'attachments' => 0,
                    ],
                    [
                        'subject' => 'Test4: from/to/subject',
                        'from' => 'from4@example.com',
                        'to' => 'to4@example.org',
                        'attachments' => 0,
                    ],
                ],
            ],
            'mails with attachments' => [
                'Acceptance/Fixtures/mail-multiple.log',
                [
                    [
                        'subject' => 'Test1: TYPO3 loves you - here is why',
                        'from' => 'hello-world@example.com',
                        'to' => 'contact@example.org',
                        'attachments' => 2,
                    ],
                    [
                        'subject' => 'Test2: TYPO3 loves you - here is why',
                        'from' => 'hello-world@example.com',
                        'to' => 'contact@example.org',
                        'attachments' => 2,
                    ],
                    [
                        'subject' => 'Test3: TYPO3 loves you - here is why',
                        'from' => 'hello-world@example.com',
                        'to' => 'contact@example.org',
                        'attachments' => 2,
                    ],
                    [
                        'subject' => 'Test4: TYPO3 loves you - here is why',
                        'from' => 'hello-world@example.com',
                        'to' => 'contact@example.org',
                        'attachments' => 0,
                    ],
                ],
            ],
        ];
    }

    /**
     * @dataProvider multipleMailsDataProvider
     * @param array<int, array{subject: string, from: string, to: string, attachments: int}> $expectedMails
     */
    #[DataProvider('multipleMailsDataProvider')]
    public function testSplittingOfMultipleMails(string $transportMboxFile, array $expectedMails): void
    {
        $this->subject->setFileContent(file_get_contents(__DIR__ . '/../../' . $transportMboxFile) ?: '');
        $this->subject->extractMessages();

        $messages = $this->subject->getMessages();
        self::assertCount(count($expectedMails), $messages);

        foreach ($expectedMails as $key => $expectedMail) {
            self::assertEquals($expectedMail['subject'], $messages[$key]->subject);
            self::assertEquals($expectedMail['from'], $messages[$key]->from);
            self::assertEquals($expectedMail['to'], $messages[$key]->to);
            self::assertCount($expectedMail['attachments'], $messages[$key]->attachments);
        }
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
