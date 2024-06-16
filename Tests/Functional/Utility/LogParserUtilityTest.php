<?php

namespace Xima\XimaTypo3Mailcatcher\Tests\Functional\Controller;

use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mime\Address;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Mail\FluidEmail;
use TYPO3\CMS\Core\Mail\MailerInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use Xima\XimaTypo3Mailcatcher\Domain\Model\Dto\MailMessage;
use Xima\XimaTypo3Mailcatcher\Utility\LogParserUtility;

class LogParserUtilityTest extends FunctionalTestCase
{
    protected LogParserUtility $subject;

    protected array $configurationToUseInTestInstance = [
        'MAIL' => [
            'transport' => 'mbox',
            'templateRootPaths' => [700 => 'EXT:xima_typo3_mailcatcher/Tests/Fixtures'],
        ],
    ];

    protected array $testExtensionsToLoad = [
        'typo3conf/ext/xima_typo3_mailcatcher',
    ];

    /**
     * @return array<int, array<int, array<int, array<string, array<int, array<string, string>>|string>>>>
     */
    public static function mailDataProvider(): array
    {
        $defaultMail = [
            'to' => 'contact@example.org',
            'toName' => 'Contact',
            'from' => 'hello-world@example.com',
            'fromName' => 'Test',
            'cc' => [
                [
                    'email' => 'cc1@example.com',
                    'name' => 'CC1',
                ],
                [
                    'email' => 'cc2@example.com',
                    'name' => 'CC2',
                ],
            ],
            'bcc' => [
                [
                    'email' => 'bcc1@example.com',
                    'name' => 'BCC1',
                ],
            ],
            'subject' => 'TYPO3 loves you - here is why',
            'template' => 'TestMailTemplate',
            'format' => FluidEmail::FORMAT_BOTH,
        ];

        return [
            [
                [$defaultMail],
            ],
            [
                [
                    $defaultMail,
                    $defaultMail,
                ],
            ],
            [
                [
                    $defaultMail,
                ],
            ],
        ];
    }

    /**
     * @dataProvider mailDataProvider
     * @param array<int, array<string, string>> $exampleMails
     * @throws TransportExceptionInterface
     */
    public function testEmailEncoding(array $exampleMails): void
    {
        foreach ($exampleMails as $key => $exampleMail) {
            $this->createTestMail($exampleMail);
        }

        $this->subject->run();
        $messages = $this->subject->getMessages();

        self::assertCount(count($exampleMails), $messages);

        foreach ($exampleMails as $key => $exampleMail) {
            self::testMailMessage($messages[$key], $exampleMail);
        }
    }

    /**
     * @param array<string, string> $exampleMail
     * @throws TransportExceptionInterface
     */
    private function createTestMail(array $exampleMail): void
    {
        $email = new FluidEmail();
        $email
            ->to(new Address($exampleMail['to'], $exampleMail['toName']))
            ->from(new Address($exampleMail['from'], $exampleMail['fromName']))
            ->format($exampleMail['format'])
            ->subject($exampleMail['subject'])
            ->setTemplate('TestMailTemplate');

        foreach ($exampleMail['cc'] as $cc) {
            $email->addCc(new Address($cc['email'], $cc['name']));
        }

        foreach ($exampleMail['bcc'] as $bcc) {
            $email->addBcc(new Address($bcc['email'], $bcc['name']));
        }

        // since typo3 version >12.1 MailerInterface is used
        if (class_exists(\Symfony\Component\Mailer\MailerInterface::class)) {
            GeneralUtility::makeInstance(\Symfony\Component\Mailer\MailerInterface::class)->send($email);
        } else {
            GeneralUtility::makeInstance(\TYPO3\CMS\Core\Mail\Mailer::class)->send($email);
        }
    }

    /**
     * @param array<string, string> $exampleMail
     */
    protected static function testMailMessage(MailMessage $message, array $exampleMail): void
    {
        if ($exampleMail['format'] === FluidEmail::FORMAT_HTML || $exampleMail['format'] === FluidEmail::FORMAT_BOTH) {
            self::assertEmailFileEqualsString(
                'EXT:xima_typo3_mailcatcher/Tests/Fixtures/' . $exampleMail['template'] . '.html',
                $message->bodyHtml
            );
        }
        if ($exampleMail['format'] === FluidEmail::FORMAT_PLAIN || $exampleMail['format'] === FluidEmail::FORMAT_BOTH) {
            self::assertEmailFileEqualsString(
                'EXT:xima_typo3_mailcatcher/Tests/Fixtures/' . $exampleMail['template'] . '.txt',
                $message->bodyPlain
            );
        }
        self::assertEquals($exampleMail['to'], $message->to);
        self::assertEquals($exampleMail['toName'], $message->toName);
        self::assertEquals($exampleMail['from'], $message->from);
        self::assertEquals($exampleMail['fromName'], $message->fromName);
        self::assertEquals($exampleMail['subject'], $message->subject);

        foreach ($exampleMail['cc'] as $key => $cc) {
            self::assertEquals($cc['email'], $message->ccRecipients[$key]['email']);
            self::assertEquals($cc['name'], $message->ccRecipients[$key]['name']);
        }

        self::assertEmpty($message->bccRecipients);
    }

    public static function assertEmailFileEqualsString(string $emailPath, string $string, string $message = null): void
    {
        $emailPath = GeneralUtility::getFileAbsFileName($emailPath);
        self::assertFileExists($emailPath);
        $emailContent = file_get_contents($emailPath);
        $emailContentWithoutBreaks = \trim(\preg_replace('/\s+/', ' ', $emailContent ?: '') ?? '');
        if ($emailContentWithoutBreaks !== $string) {
            file_put_contents(Environment::getVarPath() . '/log/' . md5($emailPath) . '_original.html', $string);
            file_put_contents(Environment::getVarPath() . '/log/' . md5($emailPath) . '_fail.html', $emailContent);
        }
        self::assertEquals(
            $emailContentWithoutBreaks,
            $string,
            ($message ?? '') . 'Messages have been saved to ' . Environment::getVarPath() . '/log/'
        );
    }

    protected function setUp(): void
    {
        parent::setUp();
        $GLOBALS['TYPO3_CONF_VARS']['MAIL']['transport_mbox_file'] = Environment::getVarPath() . '/log/mail.log';
        $this->subject = new LogParserUtility();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        GeneralUtility::rmdir($this->subject::getTempPath(), true);
        unset($this->subject, $GLOBALS['TYPO3_CONF_VARS']['MAIL']['transport_mbox_file']);
    }
}
