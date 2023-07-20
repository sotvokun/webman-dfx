<?php

namespace Sotvokun\Webman\Dfx\Exception;

use Exception;

class ActionAttributeException extends Exception
{
    public const METHOD_NOT_ALLOWED = 1;
    public const AJAX_ONLY = 2;
    public const JSON_ONLY = 3;

    public readonly int $type;

    public function __construct(int $type, array $args = [])
    {
        $this->type = $type;
        $message = match($type) {
            1 => 'Method ' . $args['method'] ?? 'UNKNOWN' . 'not allowed, only accept ' . implode(', ', $args['allowMethods']),
            2 => 'Only accept ajax request',
            3 => 'Only accept json request',
            default => 'Unknown error'
        };
        parent::__construct($message);
    }
}
