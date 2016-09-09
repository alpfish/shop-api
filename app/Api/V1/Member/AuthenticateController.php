<?php

namespace Api\V1\Member;

use App\Repositories\Member\MemberRepository as Member;
use Api\V1\Controller;

class AuthenticateController extends Controller
{
    public function register()
    {
        $rules = [
            'username' => ['required', 'alpha'],
            'password' => ['required', 'min:7']
        ];

        $payload = app('request')->only('username', 'password');

        $validator = app('validator')->make($payload, $rules);

        if ($validator->fails()) {
            throw new \Dingo\Api\Exception\StoreResourceFailedException('Could not create new user.', $validator->errors());
        }

        return $this->response->error('请求未授权.', 401);
    }

    public function login()
    {
        return Member::jwtUser();
    }

    public function logout()
    {

    }

    public function resetPassword()
    {

    }
}