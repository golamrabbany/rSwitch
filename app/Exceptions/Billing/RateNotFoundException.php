<?php

namespace App\Exceptions\Billing;

use RuntimeException;

class RateNotFoundException extends RuntimeException
{
    public function __construct(
        public readonly string $destination,
        public readonly int $rateGroupId,
        string $message = '',
    ) {
        parent::__construct(
            $message ?: "No active rate found for destination '{$destination}' in rate group {$rateGroupId}"
        );
    }
}
