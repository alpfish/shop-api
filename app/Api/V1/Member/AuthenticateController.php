<?php

namespace Api\V1\Member;

use App\Repositories\Member\MemberRepository as Member;
use Api\V1\Controller;

class AuthenticateController extends Controller
{
    public function register()
    {
        $rules = [
            'username' => 'required|min:2|max:255|regex:/^[\x{4e00}-\x{9fa5}A-Za-z0-9_]+$/u|unique:member',
            'mobile'   => 'sometimes|required|regex:/^1[34578][0-9]{9}$/|unique:member',
            'email'    => 'sometimes|required|email|unique:member',
            'password' => 'required|confirmed|min:6', // confirmed: password && password_confirmation
        ];

        $payload = app('request')->all();

        $validator = app('validator')->make($payload, $rules);

        // TODO 短信验证码校验
        $validator->sometimes('vcode', 'required', function(){
            return !!app('request')->get('mobile');
        });

        if ($validator->fails()) {
            throw new \Dingo\Api\Exception\ValidationHttpException($validator->errors());
        }

        $member = Member::createFromRequest();

        Member::setToken($member);

        return $this->response->array([ 'member' => $member->toArray() ]);
    }

    public function login()
    {
        $member   = app('request')->get('member');
        $password = app('request')->get('password');
        if (!( $member && $password )) {
            return $this->response->error('请求参数 member/password 不存在.', 400);
        }

        $field = 'username';
        if (!!preg_match('/^1[34578][0-9]{9}$/', $member)) {
            $field = 'mobile';
        }
        if (!!preg_match('/^(\w{1,25})@(\w{1,16})(\.(\w{1,4})){1,3}$/', $member)) {
            $field = 'email';
        }
        $payload   = [
            $field => $member,
            'password'  => $password,
        ];
        $rules     = [
            'username' => 'sometimes|required|exists:member,username,islock,0',
            'mobile'   => 'sometimes|required|exists:member,mobile,islock,0',
            'email'    => 'sometimes|required|exists:member,email,islock,0',
            'password' => 'required',
        ];
        $validator = app('validator')->make($payload, $rules);
        if ($validator->fails()) {
            throw new \Dingo\Api\Exception\ValidationHttpException($validator->errors());
        }

        $member = Member::login($field, $member, $password);
        if (!$member) {
            throw new \Dingo\Api\Exception\ValidationHttpException([ 'password' => '密码不正确' ]);
        }

        return $this->response->array([ 'member' => $member->toArray() ]);
    }

    public function resetPassword()
    {

    }
}