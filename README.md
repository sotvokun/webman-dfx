# Installation
```shell
# Step 1. composer install package
> composer require -W sotvokun/webman-dfx

# Step 2. execute install script
#   Beacause the package contains a symbol,
#   webman could not automatically install it.
#   We should install plugin manually.
> php webman plugin:install sotvokun/webman/dfx
```

# Feature
## JSON-RPC
### Usage
1. Define the entry point action, all JSON-RPC request should be requested at there.
```php
namespace app\api\controller;

class IndexController
{
    // POST is best for JSON-RPC, you also can use another method.
    // JSON check is also necessary.
    #[Action('POST', json: true)]
    public function index(Request $request)
    {

    }
}
```

2. Use `Dispatcher` to handle the JSON-RPC request. It will dispatch request to corresponding functions.

    The `handle` method receive 2 arguments: the first is the request; the second is an array to define the namespace path to find corresponding functions.
```diff
namespace app\api\controller;

+ use Sotvokun\Webman\Dfx\Support\JsonRpc\Dispatcher;

class IndexController
{
    // POST is best for JSON-RPC, you also can use another method.
    // JSON check is also necessary.
    #[Action('POST', json: true)]
    public function index(Request $request)
    {
+       return Dispatcher::handle($request, ['app', 'api']);
    }
}
```

3. Define controllers under the namespace path to add the JSON-RPC methods.

    For example, if a request like this `{"method": "system.info"}`. Dispatcher will find `app\api\controller\SystemController::info`.

    Also, the method can be nested more than one namespace path. For example: `lib.utils.randomUuid` the corresponding class is `app\api\lib\controller\UtilsController::randomUuid`

```php
namespace app\api\controller;

use Sotvokun\Webman\Dfx\Support\JsonRpc\RpcException;

/* The difference between JSON-RPC method and normal action:
 * 1. JSON-RPC return the value directly, Dispatcher will convert it to JSON-RPC response.
 * 2. JSON-RPC do not need `$request' as parameter. All parameters are the method needed.
 * 3. Throwing `RpcException` to generate the error response. You do not care how to response.
 */
class SystemController
{
    public function info()
    {
        return [
            'version' => '2.0'
        ];
    }

    /*
     * {"method": "system.addOne", "params": {"value": 1}}
     */
    public function addOne(int $value)
    {
        return $value + 1;
        /**
         * {"result": 2}
         */
    }

    public function fail()
    {
        throw new RpcException(1, 'failed', []);
        /**
         * {"error": {"code": 1, "message": "failed", "data": []}}
         */
    }
}
```

