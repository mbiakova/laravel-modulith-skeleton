<?php

declare(strict_types=1);

namespace Shared\Contracts;

/** An exception that renders as the API error envelope { success:false, code, message }. */
interface RendersApiEnvelope
{
    /** Machine-readable error identifier. */
    public function code(): string;

    /** Human-readable (possibly localized) message. */
    public function message(): string;

    public function httpCode(): int;
}
