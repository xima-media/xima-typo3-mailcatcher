<?php

namespace Xima\XimaTypo3Mailcatcher\Tests\Acceptance\Support\Helper;

use TYPO3\TestingFramework\Core\Acceptance\Helper\AbstractModalDialog;
use Xima\XimaTypo3Mailcatcher\Tests\Acceptance\Support\AcceptanceTester;

class ModalDialog extends AbstractModalDialog
{
    /**
     * @param AcceptanceTester $I
     */
    public function __construct(AcceptanceTester $I)
    {
        $this->tester = $I;
    }
}
