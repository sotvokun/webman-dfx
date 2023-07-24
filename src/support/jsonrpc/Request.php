<?php

namespace Sotvokun\Webman\Dfx\Support\JsonRpc;

use Webman\Http\Request as HttpRequest;

class Request
{
    public readonly string $jsonrpc;
    public readonly string $method;
    public readonly ?array $params;
    public readonly string|int|null $id;

    public function __construct(string $jsonrpc, string $method, ?array $params = null, string|int|null $id = null)
    {
        $this->jsonrpc = $jsonrpc;
        $this->method = $method;
        $this->params = $params;
        $this->id = $id;
    }

    public static function fromHttpRequest(HttpRequest $request): self
    {
        try {
            $rawBody = $request->rawBody();
            $jsonBody = json_decode($rawBody, true, 512, JSON_THROW_ON_ERROR);

            if (!key_exists('jsonrpc', $jsonBody) || $jsonBody['jsonrpc'] !== '2.0') {
                throw new \Exception('Invalid JSON-RPC version');
            }
            if (!key_exists('method', $jsonBody) || !is_string($jsonBody['method'])) {
                throw new \Exception('Invalid JSON-RPC request method');
            }
            if (key_exists('params', $jsonBody) && !is_array($jsonBody['params'])) {
                throw new \Exception('Invalid JSON-RPC request params');
            }
            if (!key_exists('id', $jsonBody) || (!is_string($jsonBody['id']) && !is_int($jsonBody['id']) && !is_null($jsonBody['id']))) {
                throw new \Exception('Invalid JSON-RPC request id');
            }

            return new self($jsonBody['jsonrpc'], $jsonBody['method'], $jsonBody['params'] ?? [], $jsonBody['id'] ?? null);

        } catch (\JsonException $e) {
            $json_error_msg = json_last_error_msg();
            throw new \Exception("JSON decoding failed ({$json_error_msg})");
        } catch (\Throwable $e) {
            throw new \Exception('Invalid JSON');
        }
    }
}
