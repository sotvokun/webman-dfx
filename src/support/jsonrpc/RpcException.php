<?php

namespace Sotvokun\Webman\Dfx\Support\JsonRpc;

class RpcException extends \Exception
{
    private Error $_error;

    public function __construct(int $code, string $message = '', mixed $data = null)
    {
        $this->_error = new Error($code, $message, $data);
        parent::__construct($this->_error->message, $code);
    }

    public function toResponse(string|int|null $id = null): Response
    {
        return Response::error($this->_error->code, $this->_error->message, $this->_error->data, $id);
    }
}
