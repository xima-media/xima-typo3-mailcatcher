<?php

namespace Xima\XimaTypo3Mailcatcher\Tests\Acceptance;

use Codeception\Attribute\Depends;
use function PHPUnit\Framework\assertEmpty;
use Xima\XimaTypo3Mailcatcher\Tests\Acceptance\Support\AcceptanceTester;
use Xima\XimaTypo3Mailcatcher\Tests\Acceptance\Support\Helper\ExtensionConfiguration;
use Xima\XimaTypo3Mailcatcher\Tests\Acceptance\Support\Helper\ModalDialog;

class BackendCest
{
    protected const MAIL_LOG_DIR = '/var/www/html/var/log';

    public function _before(AcceptanceTester $I): void
    {
        $I->amOnPage('/typo3/');
        $I->waitForElementVisible('input[name="username"]');
        $I->waitForElementVisible('input[type="password"]');
        $I->fillField('input[name="username"]', 'admin');
        $I->fillField('input[type="password"]', 'Passw0rd!');
        $I->click('button[type="submit"]');
        $I->waitForElementNotVisible('form[name="loginform"]');
        $I->seeCookie('be_typo_user');
    }

    // tests
    public function moduleNotVisible(AcceptanceTester $I, ExtensionConfiguration $extensionConfiguration): void
    {
        $extensionConfiguration->write('transport', 'sendmail');
        $I->wait(2);
        $extensionConfiguration->flushCache();
        $I->reloadPage();
        $I->dontSee('Mail Log');
    }

    public function moduleVisible(AcceptanceTester $I, ExtensionConfiguration $extensionConfiguration): void
    {
        $extensionConfiguration->write('transport', 'mbox');
        $extensionConfiguration->write('transport_mbox_file', '/var/www/html/var/log/mail.log');
        $I->wait(2);
        $extensionConfiguration->flushCache();
        $I->reloadPage();
        $I->see('Mail Log');
    }

    public function seeEnvTestMail(AcceptanceTester $I): void
    {
        $I->cleanDir(self::MAIL_LOG_DIR);
        $testMail = file_get_contents(__DIR__ . '/Fixtures/mail-env-tool.log') ?: '';
        $I->writeToFile(self::MAIL_LOG_DIR . '/mail.log', $testMail);
        $I->click('Mail Log');
        $I->switchToContentFrame();
        $I->see('TYPO3 CMS install tool <hello@example.com>');
        $I->see('Test TYPO3 CMS mail delivery from site "New TYPO3 site"');
        assertEmpty(file_get_contents(self::MAIL_LOG_DIR . '/mail.log'));
    }

    /**
     * @Depends seeEnvTestMail
     */
    public function deleteEnvTestMail(AcceptanceTester $I, ModalDialog $modalDialog): void
    {
        $I->click('Mail Log');
        $I->switchToContentFrame();
        $I->click('Delete all messages');
        $modalDialog->canSeeDialog();
        $modalDialog->clickButtonInDialog('Yes');
        $I->switchToContentFrame();
        $I->see('No messages');
    }
}
