<?php

namespace Sotvokun\Webman\Dfx\Attribute;

use Attribute;
use Exception;
use ReflectionMethod;
use Webman\Http\Request;
use Webman\Http\Response;
use Sotvokun\Webman\Dfx\Exception\ActionAttributeException;
use Webman\MiddlewareInterface;

use function Sotvokun\Webman\Dfx\response;

#[Attribute(Attribute::TARGET_METHOD)]
class Action implements MiddlewareInterface
{
    private const ALLOWED_METHOD = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'HEAD', 'OPTIONS'];

    public readonly array $allowMethods;
    public readonly bool $isAjax;
    public readonly bool $withJsonData;

    /**
     * @param string|array $method method(s) that action will accept
     * @param bool $ajax only accept ajax request
     * @param bool $json request must be json, and accept json response
     */
    public function __construct(string|array $method = self::ALLOWED_METHOD, bool $ajax = false, bool $json = false) {
        if (is_string($method)) {
            $method = [strtoupper($method)];
        } else {
            $method = array_map('strtoupper', $method);
        }
        $this->allowMethods = $method;

        if ($json) {
            $this->withJsonData = $json;
            $this->isAjax = true;
        } else if ($ajax) {
            $this->isAjax = true;
        }
    }

    /**
     * @param Request $request
     * @param bool $throwException
     * @return bool
     */
    public function validate(Request $request, bool $throwException = false): bool
    {
        if (!in_array($request->method(), $this->allowMethods)) {
            if ($throwException) {
                throw new ActionAttributeException(
                    ActionAttributeException::METHOD_NOT_ALLOWED,
                    ['method' => $request->method(), 'allowMethods' => $this->allowMethods]
                );
            }
            return false;
        }
        if ($this->isAjax && !$request->isAjax()) {
            if ($throwException) {
                throw new ActionAttributeException(ActionAttributeException::AJAX_ONLY);
            }
            return false;
        }
        if ($this->withJsonData &&
            (preg_match('/application\/json/i', $request->header('content-type') ?? '') === 0 || !$request->acceptJson())) {
            if ($throwException) {
                throw new ActionAttributeException(ActionAttributeException::JSON_ONLY);
            }
            return false;
        }
        return true;
    }

    public function process(Request $request, callable $next): Response
    {
        $ref = new ReflectionMethod($request->controller, $request->action);
        $attrs = $ref->getAttributes(self::class);
        if (count($attrs) === 0) {
            return $next($request);
        }

        $attr = $attrs[0]->newInstance();
        try {
            $attr->validate($request, true);
            return $next($request);
        } catch (ActionAttributeException $e) {
            return match($e->type) {
                ActionAttributeException::METHOD_NOT_ALLOWED => response(405, 'Method Not Allowed', $request->acceptJson()),
                ActionAttributeException::AJAX_ONLY => response(400, 'Bad Request', $request->acceptJson()),
                ActionAttributeException::JSON_ONLY => response(400, 'Bad Request', $request->acceptJson()),
                default => response(400, 'Bad Request', $request->acceptJson()),
            };
        }
    }
}
