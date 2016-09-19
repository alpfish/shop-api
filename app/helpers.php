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
        static $member = 'Unauthorized';
        if ($member === 'Unauthorized') {
            $member = App\Repositories\Member\MemberRepository::tokenMember();
        }
        return $member;
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



// 临时, 数据工厂用，可删除
function getChinese($len) {
    $str = '';
    for($i=0;$i<$len;$i++) {
        $str = $str . getChineseChar();
    }
    return $str;
}

function getChineseChar() {
    //$unidec = rand(hexdec('4e00'), hexdec('9fa5'));
    $unidec = rand(hexdec('4e00'), hexdec('4e92'));
    $unichr = '&#' . $unidec . ';';
    $zhcnchr = mb_convert_encoding($unichr, "UTF-8", "HTML-ENTITIES");
    return $zhcnchr;
}

function rand_string($len = 6, $type = '', $addChars = '') {
    $str = '';
    switch ($type) {
        case 0 :
            $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz' . $addChars;
            break;
        case 1 :
            $chars = str_repeat('0123456789', 3);
            break;
        case 2 :
            $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ' . $addChars;
            break;
        case 3 :
            $chars = 'abcdefghijklmnopqrstuvwxyz' . $addChars;
            break;
        default :
            // 默认去掉了容易混淆的字符oOLl和数字01，要添加请使用addChars参数
            $chars = 'ABCDEFGHIJKMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789' . $addChars;
            break;
    }
    if ($len > 10) { //位数过长重复字符串一定次数
        $chars = $type == 1 ? str_repeat($chars, $len) : str_repeat($chars, 5);
    }
    if ($type != 4) {
        $chars = str_shuffle($chars);
        $str = substr($chars, 0, $len);
    } else {
        // 中文随机字
        for ($i = 0; $i < $len; $i++) {
            $str .= msubstr($chars, floor(mt_rand(0, mb_strlen($chars, 'utf-8') - 1)), 1);
        }
    }
    return $str;
}
