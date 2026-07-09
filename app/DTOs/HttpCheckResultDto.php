<?php

namespace App\DTOs;

readonly class HttpCheckResultDto
{
    public function __construct(
        public bool $success,
        public ?int $statusCode = null,
        public ?int $responseTimeMs = null,
        public ?string $errorMessage = null,
    ) {}
}
