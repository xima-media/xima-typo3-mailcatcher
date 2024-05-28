<?php

namespace Xima\XimaTypo3Mailcatcher\Tests\Unit\Domain\Model\Dto;

use TYPO3\TestingFramework\Core\Unit\UnitTestCase;
use Xima\XimaTypo3Mailcatcher\Domain\Model\Dto\MailMessage;

class MailMessageTest extends UnitTestCase
{
    public function testGetFileNameWithDate(): void
    {
        $mailMessage = new MailMessage();
        $mailMessage->messageId = 'testMessageId';
        $mailMessage->date = new \DateTime('2022-01-01');

        self::assertEquals('1640995200-912a2485140b51754a279414c8780dd5.json', $mailMessage->getFileName());
    }

    public function testGetFileNameWithoutDate(): void
    {
        $mailMessage = new MailMessage();
        $mailMessage->messageId = 'testMessageId';

        self::assertEquals('912a2485140b51754a279414c8780dd5', $mailMessage->getFileName());
    }

    public function testLoadFromJson(): void
    {
        $mailMessage = new MailMessage();
        $data = ['messageId' => 'testMessageId', 'date' => '2022-01-01', 'subject' => 'testSubject'];
        $mailMessage->loadFromJson($data);

        self::assertEquals('testMessageId', $mailMessage->messageId);
        self::assertEquals(new \DateTime('2022-01-01'), $mailMessage->date);
        self::assertEquals('testSubject', $mailMessage->subject);
    }

    public function testGetDisplayFromAddressWithName(): void
    {
        $mailMessage = new MailMessage();
        $mailMessage->from = 'test@example.com';
        $mailMessage->fromName = 'Test User';

        self::assertEquals('Test User <test@example.com>', $mailMessage->getDisplayFromAddress());
    }

    public function testGetDisplayFromAddressWithoutName(): void
    {
        $mailMessage = new MailMessage();
        $mailMessage->from = 'test@example.com';

        self::assertEquals('test@example.com', $mailMessage->getDisplayFromAddress());
    }

    public function testGetDisplayToAddressWithName(): void
    {
        $mailMessage = new MailMessage();
        $mailMessage->to = 'test@example.com';
        $mailMessage->toName = 'Test User';

        self::assertEquals('Test User <test@example.com>', $mailMessage->getDisplayToAddress());
    }

    public function testGetDisplayToAddressWithoutName(): void
    {
        $mailMessage = new MailMessage();
        $mailMessage->to = 'test@example.com';

        self::assertEquals('test@example.com', $mailMessage->getDisplayToAddress());
    }

    public function testGetDisplayCcRecipients(): void
    {
        $mailMessage = new MailMessage();
        $mailMessage->ccRecipients = [['name' => 'Test User', 'email' => 'test@example.com']];

        self::assertEquals('Test User <test@example.com>', $mailMessage->getDisplayCcRecipients());
    }

    public function testGetDisplayBccRecipients(): void
    {
        $mailMessage = new MailMessage();
        $mailMessage->bccRecipients = [['name' => 'Test User', 'email' => 'test@example.com']];

        self::assertEquals('Test User <test@example.com>', $mailMessage->getDisplayBccRecipients());
    }
}
