<?php

namespace Xima\XimaTypo3Mailcatcher\Tests\Acceptance;

use Xima\XimaTypo3Mailcatcher\Tests\Acceptance\Support\AcceptanceTester;
use Xima\XimaTypo3Mailcatcher\Tests\Acceptance\Support\Helper\ExtensionConfiguration;

class BackendCest
{
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
        $I->wait(1);
        $extensionConfiguration->flushCache();
        $I->reloadPage();
        $I->dontSee('Mail Log');
    }

    public function moduleVisible(AcceptanceTester $I, ExtensionConfiguration $extensionConfiguration): void
    {
        $extensionConfiguration->write('transport', 'mbox');
        $extensionConfiguration->write('transport_mbox_file', '/var/www/html/var/log/mail.log');
        $I->wait(1);
        $extensionConfiguration->flushCache();
        $I->reloadPage();
        $I->see('Mail Log');
    }
}
