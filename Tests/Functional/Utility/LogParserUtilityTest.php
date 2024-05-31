<?php

namespace Xima\XimaTypo3Mailcatcher\Tests\Functional\Controller;

use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mime\Address;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Mail\FluidEmail;
use TYPO3\CMS\Core\Mail\MailerInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
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
     * @return array<int, array<string, string>>[]
     */
    public static function mailDataProvider(): array
    {
        return [
            [
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
            [
                [
                    'to' => 'contact@example.org',
                    'toName' => 'Contact',
                    'from' => 'hello-world@example.com',
                    'fromName' => 'Test',
                    'subject' => 'TYPO3 loves you - here is why',
                    'template' => 'TestMailTemplate',
                    'format' => FluidEmail::FORMAT_PLAIN,
                ],
            ],
            [
                [
                    'to' => 'contact@example.org',
                    'toName' => 'Contact',
                    'from' => 'hello-world@example.com',
                    'fromName' => 'Test',
                    'subject' => 'TYPO3 loves you - here is why',
                    'template' => 'TestMailTemplate',
                    'format' => FluidEmail::FORMAT_HTML,
                ],
            ],
        ];
    }

    /**
     * @dataProvider mailDataProvider
     * @param array<string, string> $exampleMail
     * @throws TransportExceptionInterface
     */
    public function testEmailEncoding(array $exampleMail): void
    {
        $this->createTestMail($exampleMail);
        $this->subject->run();

        $messages = $this->subject->getMessages();

        if ($exampleMail['format'] === FluidEmail::FORMAT_HTML || $exampleMail['format'] === FluidEmail::FORMAT_BOTH) {
            self::assertEmailFileEqualsString(
                'EXT:xima_typo3_mailcatcher/Tests/Fixtures/' . $exampleMail['template'] . '.html',
                $messages[0]->bodyHtml
            );
        }
        if ($exampleMail['format'] === FluidEmail::FORMAT_PLAIN || $exampleMail['format'] === FluidEmail::FORMAT_BOTH) {
            self::assertEmailFileEqualsString(
                'EXT:xima_typo3_mailcatcher/Tests/Fixtures/' . $exampleMail['template'] . '.txt',
                $messages[0]->bodyPlain
            );
        }
        self::assertEquals($exampleMail['to'], $messages[0]->to);
        self::assertEquals($exampleMail['toName'], $messages[0]->toName);
        self::assertEquals($exampleMail['from'], $messages[0]->from);
        self::assertEquals($exampleMail['fromName'], $messages[0]->fromName);
        self::assertEquals($exampleMail['subject'], $messages[0]->subject);
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
        GeneralUtility::makeInstance(MailerInterface::class)->send($email);
    }

    public static function assertEmailFileEqualsString(string $emailPath, string $string, string $message = null): void
    {
        $emailPath = GeneralUtility::getFileAbsFileName($emailPath);
        self::assertFileExists($emailPath);
        $emailContent = file_get_contents($emailPath);
        $emailContentWithoutBreaks = \trim(string: \preg_replace('/\s+/', ' ', $emailContent ?: '') ?? '');
        $emailContentWithoutBreaks .= 'f';
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
