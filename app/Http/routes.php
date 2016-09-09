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

    $api->group(['prefix' => 'member', 'namespace' => 'Api\V1\Member'], function($api){

        $api->post('register', 'AuthenticateController@register');   # 注册
        $api->post('login', 'AuthenticateController@login');        # 登录

        $api->group(['middleware' => 'token.auth'], function($api){
            $api->get('logout', 'AuthenticateController@logout');       # 退出登录
            $api->get('/', 'MemberController@getUser');                        # 查看个人信息
        });

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
use App\Models\Member\Member\Member;
use App\Repositories\Member\MemberRepository;

$app->get('/', function(){

    echo MemberRepository::setToken(Member::first());

    //    echo 'Api Home!';
    $jwt = app('tymon.jwt');
    //    $factory = app('tymon.jwt.payload.factory');
    //
    //    $token = $jwt->encode($factory->sub(1)->make());
    //
    //    ddd($token);
});
