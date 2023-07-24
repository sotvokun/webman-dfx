<?php

namespace Sotvokun\Webman\Dfx\Support\JsonRpc;

class Error
{
    const PREDEFINED_ERROS = [
        -32700 => 'Parse error',
        -32600 => 'Invalid Request',
        -32601 => 'Method not found',
        -32602 => 'Invalid params',
        -32603 => 'Internal error',
        -32000 => 'Server error',
    ];

    const PARSE_ERROR = -32700;
    const INVALID_REQUEST = -32600;
    const METHOD_NOT_FOUND = -32601;
    const INVALID_PARAMS = -32602;
    const INTERNAL_ERROR = -32603;
    const SERVER_ERROR = -32000;

    public readonly int $code;
    public readonly string $message;
    public readonly mixed $data;

    public function __construct(int $code, string $message = '', mixed $data = null)
    {
        $this->code = $code;
        if ($message === '' && key_exists($code, self::PREDEFINED_ERROS)) {
            $message = self::PREDEFINED_ERROS[$code];
        }
        $this->message = $message;
        $this->data = $data;
    }

    public function toArray(): array
    {
        $error = [
            'code' => $this->code,
            'message' => $this->message,
        ];

        if (isset($this->data)) {
            $error['data'] = $this->data;
        }

        return $error;
    }
}
