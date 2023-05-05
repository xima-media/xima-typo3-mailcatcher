<?php

namespace Xima\XimaTypo3Mailcatcher\Domain\Model\Dto;

class MailMessage
{
    public string $messageId = '';

    public ?\DateTime $date = null;

    public string $subject = '';

    public string $from = '';

    public string $fromName = '';

    public string $to = '';

    public string $toName = '';

    public string $bodyPlain = '';

    public string $bodyHtml = '';

    /** @var MailAttachment[]  */
    public array $attachments = [];

    public function getFileName(): string
    {
        $name = hash('md5', $this->messageId);

        if ($this->date) {
            $name = $this->date->getTimestamp() . '-' . $name . '.json';
        }

        return $name;
    }

    /**
     * @param mixed[] $data
     */
    public function loadFromJson(array $data): void
    {
        foreach ($data as $key => $value) {
            if ($key === 'date') {
                $this->date = new \DateTime($value);
                continue;
            }
            $this->{$key} = $value;
        }
    }

    public function getDisplayFromAddress(): string
    {
        if ($this->fromName) {
            return $this->fromName . ' <' . $this->from . '>';
        }
        return $this->from;
    }

    public function getDisplayToAddress(): string
    {
        if ($this->toName) {
            return $this->toName . ' <' . $this->to . '>';
        }
        return $this->to;
    }
}
