<?php

namespace Sotvokun\Webman\Dfx\Support\JsonRpc;

use support\Response as HttpResponse;

class Response
{
    public readonly string $jsonrpc;
    protected mixed $_result;
    protected ?Error $_error;
    public readonly string|int|null $id;

    private function __construct(string|int|null $id)
    {
        $this->jsonrpc = '2.0';
        $this->id = $id;
    }

    public static function success(mixed $result, string|int|null $id = null): self
    {
        $response = new self($id);
        $response->_result = $result;
        return $response;
    }

    public static function error(int $code, string $message = '', mixed $data = null, string|int|null $id = null): self
    {
        $response = new self($id);
        $response->_error = new Error($code, $message, $data);
        return $response;
    }

    public function toArray(): array
    {
        $response = [
            'jsonrpc' => $this->jsonrpc,
            'id' => $this->id,
        ];

        if (isset($this->_result)) {
            $response['result'] = $this->_result;
        }

        if (isset($this->_error)) {
            $response['error'] = $this->_error->toArray();
        }

        return $response;
    }

    public function toJsonResponse(): HttpResponse
    {
        return json($this->toArray());
    }
}
