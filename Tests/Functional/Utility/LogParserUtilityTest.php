<?php

namespace Xima\XimaTypo3Mailcatcher\Tests\Functional\Controller;

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

    public function testEmailEncoding(): void
    {
        $this->createTestMail();
        $this->subject->run();

        $messages = $this->subject->getMessages();

        self::assertEquals(
            'https://domain/sign-in/registration/confirmation?tx_getaccess_confirmation%5Baction%5D=user&tx_getaccess_confirmation%5Bcontroller%5D=Confirmation&tx_getaccess_confirmation%5Bhash%5D=2720ba93a3007066&cHash=3179e61306999063853b2247a1bd4d',
            $messages[0]->bodyPlain
        );
    }

    private function createTestMail(): void
    {
        $email = new FluidEmail();
        $email
            ->to('contact@example.org')
            ->from(new Address('jeremy@example.org', 'Jeremy'))
            ->format(FluidEmail::FORMAT_BOTH)
            ->subject('TYPO3 loves you - here is why')
            ->setTemplate('TestMailTemplate');
        GeneralUtility::makeInstance(MailerInterface::class)->send($email);
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
