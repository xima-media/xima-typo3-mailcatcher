<?php

namespace Xima\XimaTypo3Mailcatcher\Tests\Functional\Configuration;

use TYPO3\CMS\Backend\Module\ModuleRegistry;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class ModuleRegistrationMboxTest extends FunctionalTestCase
{
    protected array $configurationToUseInTestInstance = [
        'MAIL' => [
            'transport' => 'mbox',
        ],
    ];

    protected array $testExtensionsToLoad = [
        'xima/xima-typo3-mailcatcher',
    ];

    public function testModuleIsRegisteredForMboxTransport(): void
    {
        self::assertTrue($this->get(ModuleRegistry::class)->hasModule('system_mails'));
    }
}
