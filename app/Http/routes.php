<?php
/*
|--------------------------------------------------------------------------
| Api 路由
|--------------------------------------------------------------------------
|
| 使用 Dingo/Api（在 app.php 中注册）
| 使用 Redis 缓存（在 app.php 中注册），若配置失败自动转化为 File 缓存，缓存键名在
| App/Repositories/Caches/CacheKeysDefined.php 中定义及设置时长。
| Api 文档使用APIDOC: http://apidocjs.com/ 文档本地目录：/public/api-doc/
|
*/

$api = app('Dingo\Api\Routing\Router');

$api->version('v1', function($api){
    $api->get('/goods/search', 'App\Api\V1\Goods\GoodsList\SearchController@index');
    $api->get('/goods/category/tree', 'App\Api\V1\Goods\Category\CategoryController@getTree');

});




/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$app->get('/', function(){
    echo 'Api Home!';
});
