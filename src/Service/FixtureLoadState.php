<?php

namespace App\Service;

/**
 * Tracks doctrine:fixtures:load so subscribers skip side effects (nested flush, version bumps).
 */
final class FixtureLoadState
{
    private bool $loading = false;

    public function isLoading(): bool
    {
        return $this->loading;
    }

    public function begin(): void
    {
        $this->loading = true;
    }

    public function end(): void
    {
        $this->loading = false;
    }
}
