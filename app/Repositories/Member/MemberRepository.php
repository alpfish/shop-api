<?php


namespace App\Repositories\Member;

use App\Models\Member\Member\Member;

class MemberRepository
{
    /**
     * 获取 JWT 认证会员
     *
     * @return Member || null
     *
     * Author AlpFish 2016/9/3
     */
    static public function tokenMember()
    {
        $jwt = app('tymon.jwt');
        // jwt 可以从请求头（ Authorization: bearer XXX ）或 GET/POST/COOKIE 参数名 token 中获取 token
        // 但 Apache 服务器会摒弃 Authorization 请求头，所以前端要将 token 设置在请求参数中
        if (!$token = $jwt->getToken()) {
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
        if (!$member = Member::find($id)) {
            return null;
        }

        return $member;
    }

    /**
     * 为所给用户模型设置 token 并添加到响应头
     *
     * @param class Member
     *
     * @return string token || Exception
     *
     * Author AlpFish 2016/9/3
     */
    static public function setToken($member)
    {
        $jwt     = app('tymon.jwt');
        $factory = app('tymon.jwt.payload.factory');
        try {
            $id = $member->id;
        } catch (\Exception $e) {
            throw new \Exception('调用错误：setToken($member) 参数无效。', 500);
        }
        try {
            $token = $jwt->encode($factory->sub($id)->make());
        } catch (\Exception $e) {
            throw new \Exception('创建 Token 失败.', 500);
        }

        // 设置响应头
        header("Authorization:$token");

        return $token;
    }

    /**
     * 创建会员帐户
     *
     * @return Member || false
     *
     * Author AlpFish 2016/9/9
     */
    static public function createFromRequest()
    {
        // TODO 使用事务 回滚？
        $member = new Member;

        $member->username      = app('request')->exists('username') ? app('request')->username : '';
        $member->mobile        = app('request')->exists('mobile') ? app('request')->mobile : '';
        $member->email         = app('request')->exists('email') ? app('request')->email : '';
        $member->mobilestatus  = app('request')->exists('mobile') ? 1 : 0; // 手机认证状态（即是否通过短信验证，使用手机短信注册即为认证）
        $member->emailstatus   = 0; // 邮箱认证状态（即是否通过邮件确认）
        $member->encrypt       = random(6); // 密钥（Lumen 系统的用户密码即密钥是最安全的，这是海盗系统的规则）
        $member->password      = md5(md5(app('request')->get('password')) . $member->encrypt); // 海盗加密码规则
        $member->group_id      = 1;  // 会员等级
        $member->integral      = 0;  // 可用积分
        $member->islock        = 0;  // 是否锁定
        $member->register_time = time();    // 注册时间
        $member->register_ip   = app('request')->ip();  // 注册ip
        $member->login_time    = time();                // 登录时间，注册即登录，所以这里一起设置
        $member->login_ip      = app('request')->ip();  // 登录ip
        $member->login_num     = 1;                     // 登录次数

        return $member->save() ? $member : false;
    }

    /**
     * 登录
     *
     * @param string $field 登录字段
     * @param string $value 字段值
     * @param string $password 密码
     *
     * @return Member|null
     *
     * Author AlpFish 2016/9/10
     */
    static public function login($field, $value, $password)
    {
        $member = Member::where($field, $value)->first();
        if ($member && $member->password === md5(md5($password).$member->encrypt)) {

            self::setToken($member);

            $member->login_time = time();
            $member->login_ip   = app('request')->ip();
            $member->login_num += 1;
            $member->save();

            return $member;
        }

        return null;
    }

}