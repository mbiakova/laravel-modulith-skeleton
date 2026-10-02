<?php

declare(strict_types=1);

namespace Foundation\Common\Auth;

/** What a token proves: who it is, and whatever else its issuer stated. */
final readonly class Identity
{
    /** @param array<string, mixed> $claims */
    public function __construct(
        public int|string $id,
        public array $claims = [],
    ) {}
}
