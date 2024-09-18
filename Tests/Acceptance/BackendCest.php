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
        $I->cleanDir(self::MAIL_LOG_DIR);
        $I->cleanDir('/var/www/html/public/typo3temp/assets/xima_typo3_mailcatcher/');
        $I->amOnPage('/');
    }

    public function login(AcceptanceTester $I, ExtensionConfiguration $extensionConfiguration): void
    {
        $I->waitForElementVisible('input[name="username"]');
        $I->waitForElementVisible('input[type="password"]');
        $I->fillField('input[name="username"]', 'admin');
        $I->fillField('input[type="password"]', 'Passw0rd!');
        $I->click('button[type="submit"]');
        $I->waitForElementNotVisible('form[name="loginform"]');
        $I->seeCookie('be_typo_user');
    }

    /**
     * @Depends login
     */
    public function moduleNotVisible(AcceptanceTester $I, ExtensionConfiguration $extensionConfiguration): void
    {
        $extensionConfiguration->write('transport', 'sendmail');
        $I->wait(2);
        $extensionConfiguration->flushCache();
        $I->reloadPage();
        $I->dontSee('Mail Log');
    }

    /**
     * @Depends login
     */
    public function moduleVisible(AcceptanceTester $I, ExtensionConfiguration $extensionConfiguration): void
    {
        $extensionConfiguration->write('transport', 'mbox');
        $extensionConfiguration->write('transport_mbox_file', '/var/www/html/var/log/mail.log');
        $I->wait(2);
        $extensionConfiguration->flushCache();
        $I->reloadPage();
        $I->see('Mail Log');
    }

    /**
     * @Depends moduleVisible
     */
    public function testEnvMail(AcceptanceTester $I): void
    {
        $testMail = file_get_contents(__DIR__ . '/Fixtures/mail-env-tool.log') ?: '';
        file_put_contents(self::MAIL_LOG_DIR . '/mail.log', $testMail);
        $I->click('Mail Log');
        $I->switchToContentFrame();
        $I->waitForElement('h1');
        assertEmpty(file_get_contents(self::MAIL_LOG_DIR . '/mail.log'));
        $I->see('TYPO3 CMS install tool <hello@example.com>');
        $I->see('Test TYPO3 CMS mail delivery from site "New TYPO3 site"');
        $I->click('TYPO3 CMS install tool <hello@example.com>');
        $I->wait(1);
        $I->see('Hey TYPO3 Administrator');
        $I->click('HTML', '#m1');
        $I->wait(1);
        $I->switchToIFrame('iframe');
        $I->see('Hey TYPO3 Administrator', 'h4');
    }

    /**
     * @Depends moduleVisible
     */
    public function testMultipleMailFixture(AcceptanceTester $I, ModalDialog $modalDialog): void
    {
        // write fixtures to mail.log
        $testMail = file_get_contents(__DIR__ . '/Fixtures/mail-multiple.log') ?: '';
        file_put_contents(self::MAIL_LOG_DIR . '/mail.log', $testMail);
        $I->click('Mail Log');
        $I->switchToContentFrame();
        $I->waitForElement('h1');
        $I->seeNumberOfElements('div[data-message-file]', 4);
        // navigate to files of first mail
        $I->click('Test1');
        $I->waitForElementVisible('#m4 .btn-group.content-type-switches');
        $I->click('Files', '#m4');
        $I->waitForElementVisible('#m4 .form-section[data-content-type="files"]');
        $I->see('test.txt', '#m4');
        $I->see('test.html', '#m4');
        // delete the first mail
        $I->click('.panel[data-message-file="1725800048-74be16979710d4c4e7c6647856088456.json"] button[data-delete]');
        $I->waitForElementNotVisible('.panel[data-message-file="1725800048-74be16979710d4c4e7c6647856088456.json"]');
        $I->seeNumberOfElements('div[data-message-file]', 3);
        // delete all mails
        $I->click('Delete all messages');
        $modalDialog->canSeeDialog();
        $modalDialog->clickButtonInDialog('Yes, delete');
        $I->waitForText('All messages have been deleted');
        $I->switchToContentFrame();
        $I->see('No messages');
    }
}
