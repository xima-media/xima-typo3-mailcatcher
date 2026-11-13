<?php

namespace Xima\XimaTypo3Mailcatcher\Tests\Functional\Configuration;

use TYPO3\CMS\Backend\Module\ModuleRegistry;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class ModuleRegistrationSendmailTest extends FunctionalTestCase
{
    protected array $configurationToUseInTestInstance = [
        'MAIL' => [
            'transport' => 'sendmail',
        ],
    ];

    protected array $testExtensionsToLoad = [
        'xima/xima-typo3-mailcatcher',
    ];

    public function testModuleIsNotRegisteredForOtherTransports(): void
    {
        self::assertFalse($this->get(ModuleRegistry::class)->hasModule('system_mails'));
    }
}
