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
        if ($appUrl === null || $appUrl === '') {
            return null;
        }

        return rtrim($appUrl, '/');
    }
}
