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

    $api->get('/goods/search', 'Api\V1\Goods\GoodsList\SearchController@index');
    $api->get('/goods/category/tree', 'Api\V1\Goods\Category\CategoryController@getTree');

    // 会员
    $api->group([ 'prefix' => 'member', 'namespace' => 'Api\V1\Member' ], function($api){
        $api->post('register', 'AuthenticateController@register');
        $api->post('login', 'AuthenticateController@login'); # 获取 token 及用户
        $api->get('login/token', 'AuthenticateController@tokenLogin'); # 使用 token 登录获取用户
        $api->group([ 'middleware' => 'token.auth' ], function($api){
            $api->get('logout', 'AuthenticateController@logout');       # 退出登录
        });

    });

    // 购物车
    $api->group([ 'prefix' => 'cart', 'middleware' => 'token.auth', 'namespace' => 'Api\V1\Cart' ], function($api){
        $api->get('all', 'CartController@all');
        $api->get('add', 'CartController@add');
        $api->get('update', 'CartController@update');
        $api->get('delete', 'CartController@delete');
        $api->get('settlement', 'CartController@settlement');
    });

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
use App\Repositories\Goods\SkuRepository as Sku;

$app->get('/', function(){

    exit;

});

$app->get('/goodsprom', function(){
    return GoodsPromotion::all()->toArray();
});


function getIds($nums) {
    $ids = [];
    for ($i = 1; $i<=$nums; $i++) {
        $ids[] = mt_rand(100, 3500);
    }
    return $ids;
}