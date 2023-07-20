<?php

namespace Sotvokun\Webman\Dfx\Exception;

use Exception;

class ViewAttributeException extends Exception
{
    public const INVALID_DATA = 1;

    public readonly int $type;

    public function __construct(int $type, array $args = [])
    {
        $message = match($type) {
            1 => 'Invalid data',
            default => 'Unknown error'
        };
        parent::__construct($message);
    }
}
