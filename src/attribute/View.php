<?php

namespace Sotvokun\Webman\Dfx\Attribute;

use Attribute;
use ReflectionMethod;
use Sotvokun\Webman\Dfx\Exception\ViewAttributeException;
use Webman\Http\Request;
use Webman\Http\Response;
use Webman\MiddlewareInterface;

use function Sotvokun\Webman\Dfx\is_serialized;

#[Attribute(Attribute::TARGET_METHOD)]
class View implements MiddlewareInterface
{
    public readonly ?string $view;
    public readonly ?string $app;
    public readonly ?string $plugin;

    public function __construct(?string $view = null, ?string $app = null, ?string $plugin = null)
    {
        $this->view = $view;
        $this->app = $app;
        $this->plugin = $plugin;
    }

    public function process(Request $request, callable $next): Response
    {
        $ref = new ReflectionMethod($request->controller, $request->action);
        $attrs = $ref->getAttributes(self::class);
        if (count($attrs) === 0) {
            return $next($request);
        }

        $response = $next($request);
        if ($response->getStatusCode() !== 200) {
            return $response;
        }
        $rawBody = $response->rawBody();
        if (!is_serialized($rawBody)) {
            throw new ViewAttributeException(ViewAttributeException::INVALID_DATA);
        }
        $data = unserialize($rawBody);
        if (!is_array($data)) {
            throw new ViewAttributeException(ViewAttributeException::INVALID_DATA);
        }

        $attr = $attrs[0]->newInstance();
        $viewPath = $this->getViewPath($request->controller, $request->action, $attr->view ?? '');

        return view($viewPath, $data, $attr->app, $attr->plugin);
    }

    /**
     * Get view path
     * @param string $view
     * @param string $controller Controller name with namespace and suffix, example: `app\controller\IndexController`
     * @param string $action
     */
    private function getViewPath(string $controller, string $action, string $view = ''): string
    {
        if (strpos($view, '/') !== false) {
            return $view;
        }
        $controllerNameParts = explode('\\', $controller);
        $controllerNameWithoutSuffix = substr(end($controllerNameParts), 0, strlen(config('app.controller_suffix')) * -1);
        return strtolower($controllerNameWithoutSuffix) . '/' . (strlen($view) === 0 ? $action : $view);
    }
}
