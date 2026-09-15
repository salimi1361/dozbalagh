<?php

namespace App\Exceptions;

use RuntimeException;

class DriverDeviceBindingException extends RuntimeException
{
    public const ANOTHER_DEVICE = 'another_device';

    public const REVOKED_DEVICE = 'revoked_device';

    public function __construct(public readonly string $reason)
    {
        parent::__construct($reason);
    }
}
