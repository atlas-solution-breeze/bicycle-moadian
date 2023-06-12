<?php

namespace Package\Moadian\Dto;

class InquiryByUidDto extends PrimitiveDto
{
    private array $uid;

    public function setUid(string $uid): void
    {
        $this->uid['uid'] = $uid;
    }

    public function setFiscalId(string $fiscalId): void
    {
        $this->uid['fiscalId'] = $fiscalId;
    }

    public function getUid(): array
    {
        return [$this->uid];
    }
}
