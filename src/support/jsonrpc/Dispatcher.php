<?php

namespace Sotvokun\Webman\Dfx\Support\JsonRpc;

use ReflectionMethod;
use Webman\Http\Request as HttpRequest;
use Webman\Http\Response as HttpResponse;

use function Sotvokun\Webman\Dfx\response;

class Dispatcher
{
    public static function handle(HttpRequest $request, array $namespacePath): HttpResponse
    {
        if ($request->method() !== 'POST') {
            return response(405, 'Method Not Allowed', $request->acceptJson());
        }
        if (preg_match('/^application\/json/', $request->header('Content-Type')) !== 1) {
            return response(415, 'Unsupported Content-Type', $request->acceptJson());
        }

        try {
            $rpcRequest = Request::fromHttpRequest($request);
            [$controller, $action] = self::parseMethod($rpcRequest->method, $namespacePath);

            if (!method_exists($controller, $action)) {
                return Response::error(Error::METHOD_NOT_FOUND, '', null, $rpcRequest->id)->toJsonResponse();
            }

            $methodRef = new ReflectionMethod($controller, $action);
            $arguments = self::parseArguments($rpcRequest->params, $methodRef);

            $result = $methodRef->invokeArgs(new $controller, $arguments);
            return Response::success($result, $rpcRequest->id)->toJsonResponse();
        } catch (RpcException $e) {
            return $e->toResponse($rpcRequest->id ?? null)->toJsonResponse();
        } catch (\Exception $e) {
            return Response::error(Error::INVALID_REQUEST, $e->getMessage(), $e, null)->toJsonResponse();
        }
    }

    private static function parseMethod(string $method, array $namespacePath): array
    {
        $methodPath = explode('.', $method);
        if (count($methodPath) < 2) {
            throw new RpcException(Error::INVALID_REQUEST);
        }

        $controllerAndAction = array_slice($methodPath, -2);
        if (count($methodPath) > 2) {
            $namespacePath = array_merge(
                $namespacePath,
                array_slice($methodPath, 0, -2)
            );
        }
        $namespacePath[] = 'controller';
        $namespacePath[] = ucfirst($controllerAndAction[0]) . config('app.controller_suffix', 'Controller');
        return [
            implode('\\', $namespacePath),
            $controllerAndAction[1]
        ];
    }

    private static function parseArguments(array $args, ReflectionMethod $methodRef): array
    {
        $methodParams = $methodRef->getParameters();
        $arguments = [];
        foreach ($methodParams as $param) {
            $paramName = $param->getName();
            if (key_exists($paramName, $args)) {
                $arguments[] = $args[$paramName];
                continue;
            }
            if ($param->isDefaultValueAvailable()) {
                $arguments[] = $param->getDefaultValue();
                continue;
            }
            throw new RpcException(Error::INVALID_PARAMS);
        }
        return $arguments;
    }
}
