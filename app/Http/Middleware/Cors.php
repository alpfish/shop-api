<?php

namespace App\Http\Middleware;

use Closure;

/**
 * 使用 CORS 处理跨域请求
 *
 * 原理：
 *
 * http://www.cnblogs.com/dojo-lzz/p/4265637.html
 *
 * 使用 Authorization：
 *
 * 浏览器正常情况只能获取简单响应头数据，如 Cache-Control， Content-Language， Content-Type， Expires，
 * Last-Modified， Pragma。
 * 如果要让浏览器能够获取其他头数据，必须在服务器设置返回 Access-Control-Expose-Headers 响应头，其内容为
 * 可以让浏览器获取的头列表，如 Authorization。
 * http://stackoverflow.com/questions/34852118/vue-resource-headers-from-the-cross-origin-response-is-not-fully-available
 */

class Cors
{
    /**
     * Create a new middleware instance.
     *
     * @param \Illuminate\Contracts\Auth\Factory $auth
     */
    public function __construct()
    {
    }

    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure                 $next
     * @param string|null              $guard
     *
     * @return mixed
     */
    public function handle($request, Closure $next, $guard = null)
    {
        $headers = [
            'Access-Control-Allow-Origin'      => '*',
            'Access-Control-Allow-Methods'     => 'GET, HEAD, POST, PUT, DELETE', 'OPTIONS',
            'Access-Control-Allow-Headers'     => 'Origin, X-Requested-With, Content-Type, Accept, Authorization',
            'Access-Control-Expose-Headers'    => 'Authorization',  // 允许浏览器可以获取 Authorization 头
            'Access-Control-Allow-Credentials' => 'true',
            'Access-Control-Max-Age'           => '60',
        ];

        // 前端使用 'OPTIONS' 会发生两次请求
        if ($request->isMethod('OPTIONS')) {
            return response(null, 200, $headers);
        }

        $response = $next($request);
        foreach ($headers as $key => $value) {
            $response->header($key, $value);
        }

        return $response;
    }
}
