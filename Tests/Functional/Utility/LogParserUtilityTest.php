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
     * @return array<string, string>[][][]
     */
    public static function mailDataProvider(): array
    {
        return [
            //[
            //    [
            //        'to' => 'contact@example.org',
            //        'toName' => 'Contact',
            //        'from' => 'hello-world@example.com',
            //        'fromName' => 'Test',
            //        'subject' => 'TYPO3 loves you - here is why',
            //        'template' => 'TestMailTemplate',
            //        'format' => FluidEmail::FORMAT_BOTH,
            //    ],
            //],
            [
                [
                    [
                        'to' => 'contact@example.org',
                        'toName' => '',
                        'from' => 'hello-world@example.com',
                        'fromName' => 'Test',
                        'subject' => 'TYPO3 loves you - here is why',
                        'template' => 'TestMailTemplate',
                        'format' => FluidEmail::FORMAT_BOTH,
                    ],
                    [
                        'to' => 'contact@example.org',
                        'toName' => 'Contact',
                        'from' => 'hello-world@example.com',
                        'fromName' => 'Test',
                        'subject' => 'TYPO3 loves you - here is why',
                        'template' => 'TestMailTemplate',
                        'format' => FluidEmail::FORMAT_BOTH,
                    ],
                ],
            ],
            //[
            //    [
            //        'to' => 'contact@example.org',
            //        'toName' => 'Contact',
            //        'from' => 'hello-world@example.com',
            //        'fromName' => 'Test',
            //        'subject' => 'TYPO3 loves you - here is why',
            //        'template' => 'TestMailTemplate',
            //        'format' => FluidEmail::FORMAT_HTML,
            //    ],
            //],
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
    }

    public static function assertEmailFileEqualsString(string $emailPath, string $string, string $message = null): void
    {
        $emailPath = GeneralUtility::getFileAbsFileName($emailPath);
        self::assertFileExists($emailPath);
        $emailContent = file_get_contents($emailPath);
        $emailContentWithoutBreaks = \trim(string: \preg_replace('/\s+/', ' ', $emailContent ?: '') ?? '');
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
