<?php
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
use App\Exceptions\ApiException;

$app->get('/', function(){
    //    throw new Exception('出错啦', 400);
    throw new ApiException([ ], 422, '验证用户名失败。');
    echo( timer() - START );
});

// 商品
$app->group([ 'prefix' => 'goods', 'namespace' => 'Api\Goods' ], function($app){
    $app->get('search', 'GoodsList\SearchController@index');
    $app->get('category/tree', 'Category\CategoryController@getTree');
});

// 会员
$app->group([ 'prefix' => 'member', 'namespace' => 'Api\Member' ], function($app){
    $app->post('register', 'AuthenticateController@register');
    $app->post('login', 'AuthenticateController@login'); # 获取 token 及用户
    $app->get('login/token', 'AuthenticateController@tokenLogin'); # 使用 token 登录获取用户

});

// 购物车
$app->group([ 'prefix' => 'cart', 'middleware' => 'jwt.auth', 'namespace' => 'Api\Cart' ], function($app){
    $app->get('all', 'CartController@all');
    $app->get('add', 'CartController@add');
    $app->get('update', 'CartController@update');
    $app->get('delete', 'CartController@delete');
    $app->get('settlement', 'CartController@settlement');
});
