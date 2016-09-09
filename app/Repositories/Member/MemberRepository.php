<?php


namespace App\Repositories\Member;

use App\Models\Member\Member\Member;

class MemberRepository
{
    /**
     * 获取 JWT 认证用户
     *
     * @return null || App\Models\Member\Member\Member
     *
     * Author AlpFish 2016/9/3
     */
    static public function tokenUser()
    {
        $jwt = app('tymon.jwt');
        // jwt 可以从请求头（ Authorization: bearer XXX ）或 GET/POST/COOKIE 参数名 token 中获取 token
        // 但 Apache 服务器会摒弃 Authorization 请求头，所以前端要将 token 设置在请求参数中
        if ( !$token = $jwt->getToken()) {
            return null;
        }

        try {
            $id = $jwt->payload()->get('sub');
        } catch (\Tymon\JWTAuth\Exceptions\TokenExpiredException $e) {
            // 自动更新过期的token并添加到响应头
            try {
                $token = $jwt->refresh($token);
                header("Authorization:$token");
                $id = $jwt->payload($token)->get('sub');
            } catch (\Exception $e) {
                return null;
            }
        } catch (\Exception $e) {
            return null;
        }

        // TODO 使用缓存
        if ( !$user = Member::find($id)) {
            return null;
        }

        return $user;
    }

    /**
     * 为所给用户模型设置 token 并添加到响应头
     *
     * @param         instance \App\Models\Member\Member\Member
     *
     * @return token || null
     *
     * Author AlpFish 2016/9/3
     */
    static public function setToken($user)
    {
        $jwt     = app('tymon.jwt');
        $factory = app('tymon.jwt.payload.factory');
        try {
            $token = $jwt->encode($factory->sub($user->id)->make());
        } catch (\Exception $e) {
            return null;
        }

        header("Authorization:$token");

        return $token;
    }

    /**
     * 更新请求中携带的 token
     *
     * @return string token
     *
     * Author AlpFish
     * Date 2016/9/3
     */
    static public function upToken()
    {

    }

    static public function create()
    {

    }

}