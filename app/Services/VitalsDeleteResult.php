<?php

namespace App\Services;

/**
 * Outcome of a vitals-version deletion attempt.
 */
final readonly class VitalsDeleteResult
{
    public function __construct(
        public bool $deleted = false,
        public bool $notFound = false,
        public ?string $error = null,
    ) {}
}
