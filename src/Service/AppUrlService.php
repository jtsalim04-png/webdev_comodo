<?php

namespace App\Service;

final class AppUrlService
{
    public function __construct(
        private readonly ?string $appUrl = null,
    ) {
    }

    public function getBaseUrl(): ?string
    {
        if ($this->appUrl === null || $this->appUrl === '') {
            return null;
        }

        return rtrim($this->appUrl, '/');
    }
}
