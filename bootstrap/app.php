<?php
// 计时函数

function timer()
{
    list($usec, $sec) = explode(" ", microtime());
    return ((float)$usec + (float)$sec);
}
define('START', timer());

// 自动加载
require_once __DIR__.'/../vendor/autoload.php';

try {
    (new Dotenv\Dotenv(__DIR__.'/../'))->load();
} catch (Dotenv\Exception\InvalidPathException $e) {
    die('.env file lose ...');
}

/*
|--------------------------------------------------------------------------
| Create The Application
|--------------------------------------------------------------------------
|
| Here we will load the environment and create the application instance
| that serves as the central piece of this framework. We'll use this
| application as an "IoC" container and router for this framework.
|
*/

$app = new Laravel\Lumen\Application(
    realpath(__DIR__.'/../')
);

// $app->withFacades();
// 使用 app('db') 代替 DB 门面

// Eloquent
$app->withEloquent();

/*
|--------------------------------------------------------------------------
| Register Container Bindings
|--------------------------------------------------------------------------
|
| Now we will register a few bindings in the service container. We will
| register the exception handler and the console kernel. You may add
| your own bindings here if you like or you can make another file.
|
*/

$app->singleton(
    Illuminate\Contracts\Debug\ExceptionHandler::class,
    App\Exceptions\Handler::class
);

$app->singleton(
    Illuminate\Contracts\Console\Kernel::class,
    App\Console\Kernel::class
);

/*
|--------------------------------------------------------------------------
| Register Middleware
|--------------------------------------------------------------------------
|
| Next, we will register the middleware with the application. These can
| be global middleware that run before and after each request into a
| route or middleware that'll be assigned to some specific routes.
|
*/

$app->middleware([
    // 使用 CORS 处理跨域请求 及 使用 Authorization 头
    App\Http\Middleware\Cors::class,
    // 获取 SQL 查询语句，生产环境注释掉
    App\Http\Middleware\GetQueryLog::class,
]);

$app->routeMiddleware([
    // api token 认证中间件, 避免与 dingo/api 和 tymon/jwt-auth 已注册的名称重名
    'token.auth' => App\Http\Middleware\TokenAuth::class,
    // 'auth' => App\Http\Middleware\Authenticate::class,
]);

/*
|--------------------------------------------------------------------------
| Register Service Providers
|--------------------------------------------------------------------------
|
| Here we will register all of the application's service providers which
| are used to bind services into the container. Service providers are
| totally optional, so you are not required to uncomment this line.
|
*/

// $app->register(App\Providers\AppServiceProvider::class);
// $app->register(App\Providers\AuthServiceProvider::class);
// $app->register(App\Providers\EventServiceProvider::class);

// 注册 Redis
$app->register(Illuminate\Redis\RedisServiceProvider::class);
try {
    //app('config')->set('cache.default', 'redis'); // 在 .env 中设置
    if (! app('redis')->set('foo', 'bar', 'px', 10)) {
        throw new Exception('Redis not working ...');
    }
} catch (Exception $e) {
    //app('config')->set('cache.default', 'file');
    die('Redis not working ...');
}

// dingo/api , 由于性能原因，使用自定义JWT认证，见 Member 仓库
$app->register(Dingo\Api\Provider\LumenServiceProvider::class);

/*
|--------------------------------------------------------------------------
| Load The Application Routes
|--------------------------------------------------------------------------
|
| Next we will include the routes file so that they can all be added to
| the application. This will provide all of the URLs the application
| can respond to, as well as the controllers that may handle them.
|
*/

$app->group(['namespace' => 'App\Http\Controllers'], function ($app) {
    require __DIR__.'/../app/Http/routes.php';
});

return $app;

