<?php

// 获取当前登录用户

if (! function_exists('auth_member')) {
    /**
     * 获取 JWT 认证用户
     *
     * @return null || App\Models\Member\Member\Member
     */
    function auth_member()
    {
        return App\Repositories\Member\MemberRepository::tokenMember();
    }
}







// haidao 辅助函数

/**
 * 随机字符串
 * @param int $length 长度
 * @param int $numeric 类型(0：混合；1：纯数字)
 * @return string
 */
function random($length, $numeric = 0) {
    $seed = base_convert(md5(microtime().$_SERVER['DOCUMENT_ROOT']), 16, $numeric ? 10 : 35);
    $seed = $numeric ? (str_replace('0', '', $seed).'012340567890') : ($seed.'zZ'.strtoupper($seed));
    if($numeric) {
        $hash = '';
    } else {
        $hash = chr(rand(1, 26) + rand(0, 1) * 32 + 64);
        $length--;
    }
    $max = strlen($seed) - 1;
    for($i = 0; $i < $length; $i++) {
        $hash .= $seed{mt_rand(0, $max)};
    }
    return $hash;
}
