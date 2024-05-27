<?php

namespace Xima\XimaTypo3Mailcatcher\Tests\Unit\Utility;

use TYPO3\TestingFramework\Core\Unit\UnitTestCase;
use Xima\XimaTypo3Mailcatcher\Utility\LogParserUtility;

class LogParserUtilityTest extends UnitTestCase
{
    protected LogParserUtility $subject;

    public function testLoadAndGetMessages(): void
    {

    }

    public function testLoadMessages(): void
    {

    }

    public function testDeleteMessages(): void
    {

    }

    public function testDeleteMessageByFilename(): void
    {

    }

    public function testGetPublicPath(): void
    {

    }

    public function testGetMessageByFilename(): void
    {

    }

    public function testGetTempPath(): void
    {
        self::assertGreaterThan(2, strlen($this->subject::getTempPath()));
        self::assertDirectoryExists($this->subject::getTempPath());
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = new LogParserUtility();
    }
}
