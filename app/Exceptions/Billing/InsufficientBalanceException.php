<?php

namespace App\Exceptions\Billing;

use RuntimeException;

class InsufficientBalanceException extends RuntimeException
{
    public function __construct(
        public readonly int $userId,
        public readonly string $required,
        public readonly string $available,
    ) {
        parent::__construct(
            "Insufficient balance for user {$userId}: requires {$required}, available {$available}"
        );
    }
}
