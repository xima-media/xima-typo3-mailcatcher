<?php

namespace Xima\XimaTypo3Mailcatcher\Domain\Model\Dto;

class MailAttachment
{
    public string $filename = '';

    public int $filesize = 0;

    public string $publicPath = '';
}
