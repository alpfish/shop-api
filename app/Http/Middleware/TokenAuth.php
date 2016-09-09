<?php

namespace App\Http\Middleware;

use Closure;

class TokenAuth
{
    /**
     * 用户认证中间件（借用 tymon/jwt-auth 实现）
     *
     * 用户注册或登录成功，后台发送 token 响应头到浏览器。前端请求携带 token 以获取身份认证。
     * 使用 GET/POST 请求认证参数名为 'token'，使用 COOKIE 携带认证参数名也为 'token',
     * 在请求头文件中携带认证格式为：Authorization: bearer XXXXXXX .
     *
     * 性能：系统处理一次认证需要花费较长时间 0.1s~0.3s，所以只在需要的地方设置
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (!auth_member()) {
            return response('Unauthorized.', 401);
            // throw new \Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException('认证失败.');
        }

        return $next($request);
    }
}
