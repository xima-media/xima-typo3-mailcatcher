<?php

declare(strict_types=1);

namespace Xima\XimaTypo3Mailcatcher\Tests\Acceptance\Support;

use TYPO3\TestingFramework\Core\Acceptance\Step\FrameSteps;/**
 * Inherited Methods
 * @method void wantTo($text)
 * @method void wantToTest($text)
 * @method void execute($callable)
 * @method void expectTo($prediction)
 * @method void expect($prediction)
 * @method void amGoingTo($argumentation)
 * @method void am($role)
 * @method void lookForwardTo($achieveValue)
 * @method void comment($description)
 * @method void pause($vars = [])
 * @SuppressWarnings(PHPMD)
*/
class AcceptanceTester extends \Codeception\Actor
{
    use \Xima\XimaTypo3Mailcatcher\Tests\Acceptance\_generated\AcceptanceTesterActions;

    /**
     * Define custom actions here
     */
    use FrameSteps;
}
