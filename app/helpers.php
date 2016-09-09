<?php

// 获取当前登录用户

if (! function_exists('auth_user')) {
    /**
     * 获取 JWT 认证用户
     *
     * @return null || App\Models\Member\Member\Member
     */
    function auth_user()
    {
        return App\Repositories\Member\MemberRepository::tokenUser();
    }
}
