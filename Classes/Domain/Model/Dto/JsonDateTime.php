<?php

namespace Xima\XimaTypo3Mailcatcher\Domain\Model\Dto;

class JsonDateTime extends \DateTime implements \JsonSerializable
{
    #[\ReturnTypeWillChange]
    public function jsonSerialize(): string
    {
        return $this->format('c');
    }
}
