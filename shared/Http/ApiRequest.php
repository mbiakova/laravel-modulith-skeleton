<?php

declare(strict_types=1);

namespace Shared\Http;

use Illuminate\Foundation\Http\FormRequest;
use Shared\Auth\HasPrincipal;

abstract class ApiRequest extends FormRequest
{
    use HasPrincipal;

    final public function authorize(): bool
    {
        return true; // authorization is delegated to Policies
    }
}
