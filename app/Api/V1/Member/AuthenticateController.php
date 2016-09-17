<?php

namespace Api\V1\Member;

use App\Repositories\Member\MemberRepository as Member;
use Api\V1\Controller;

class AuthenticateController extends Controller
{
    /****************************************************************************************
     *
     * @by              Alpfish 2016/9/10 9:04
     *
     * @api             {POST} member/register 会员注册
     * @apiName         register
     * @apiGroup        Member
     * @apiVersion      1.0.0
     * @apiPermission   public
     * @apiDescription  会员注册，暂不支持短信与验证码，支持会员名/手机号/邮箱字段一个或多个。
     *
     * @apiParam {string} [username] 会员名.
     * @apiParam {number} [mobile] 手机号.
     * @apiParam {string} [email] 邮箱.
     * @apiParam {string} password 密码.
     * @apiParam {string} password_confirmation 确认密码.
     *
     * @apiHeader   (请求成功) {string}  Authorization 会员的认证 Token ,
     *                                                认证请求时添加到 Authorization 头，或请求参数 token 中.
     * @apiSuccess  (请求成功) {array}  member 注册成功的会员信息.
     *
     * @apiError    (请求失败) {number}   status_code 422：参数或值错误，500：服务器错误
     * @apiError    (请求失败) {string}   message     失败信息
     * @apiError    (请求失败) {array }   errors      验证失败对应字段的错误信息
     */
    public function register()
    {
        $rules = [
            'username' => 'sometimes|required|min:2|max:255|regex:/^[\x{4e00}-\x{9fa5}A-Za-z0-9_]+$/u|unique:member',
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

        try {
            Member::setToken($member);
        } catch (\Exception $e) {
            // nothing (Token 出错不影响注册)
        }

        return $this->response->array([ 'member' => $member->toArray() ]);
    }

    /****************************************************************************************
     * 会员登录
     * Alpfish 2016/9/10 9:22
     *
     * @api             {POST} member/register 会员登录
     * @apiName         login
     * @apiGroup        Member
     * @apiVersion      1.0.0
     * @apiPermission   public
     * @apiDescription  会员登录，前端请求携带 Token，后台需要认证时自动判断并刷新 Token，如有刷新将返回
     *                  刷新后的 Token，前端自动更新缓存，下次请求携带新的 Token. 如果超过刷新时间（两周），
     *                  后台返回 401 认证失败状态，前端重新登录获取 Token.
     *
     * @apiParam {string} account   帐号(系统自动判断 会员名/手机号/邮箱 字段)
     * @apiParam {string} password  密码
     *
     * @apiHeader   (请求成功) {string} Authorization 会员的认证 Token ,认证请求时添加到 Authorization 头，或请求参数 token 中.
     * @apiSuccess  (请求成功) {array}  member        登录成功的会员信息.
     *
     * @apiError    (请求失败) {number}   status_code 422：参数或值错误，500：服务器错误
     * @apiError    (请求失败) {string}   message     失败信息
     * @apiError    (请求失败) {array }   errors      验证失败对应字段的错误信息(account 对应的字段有：username,mobile,email)
     */
    public function login()
    {
        $account  = app('request')->get('account');
        $password = app('request')->get('password');

        $rules = [
            'username' => 'sometimes|required|exists:member,username,islock,0',
            'mobile'   => 'sometimes|required|exists:member,mobile,islock,0',
            'email'    => 'sometimes|required|exists:member,email,islock,0',
            'password' => 'required',
        ];

        $field = 'username';
        if (!!preg_match('/^1[34578][0-9]{9}$/', $account)) {
            $field = 'mobile';
        } elseif (!!preg_match('/^(\w{1,25})@(\w{1,16})(\.(\w{1,4})){1,3}$/', $account)){
            $field = 'email';
        }
        $payload = [
            $field     => $account,
            'password' => $password,
        ];

        $validator = app('validator')->make($payload, $rules);

        if ($validator->fails()) {
            throw new \Dingo\Api\Exception\ValidationHttpException($validator->errors());
        }

        $member = Member::login($field, $account, $password);
        if (!$member) {
            throw new \Dingo\Api\Exception\ValidationHttpException([ 'password' => '密码不正确' ]);
        }

        return $this->response->array([ 'member' => $member->toArray() ]);
    }

    /****************************************************************************************
     *
     * @by              Alpfish 2016/9/10 17:20
     *
     * @api             {GET} member/register TokenLogin
     * @apiName         TokenLogin
     * @apiGroup        Member
     * @apiVersion      1.0.0
     * @apiPermission   public
     * @apiDescription  使用 token 登录获取用户信息
     *
     * @apiParam {string} token
     *
     * @apiHeader   (请求成功) {string} [Authorization] 刷新的 Token ，需要刷新时才返回
     * @apiSuccess  (请求成功) {array}  member          登录成功的会员信息.
     *
     * @apiError    (请求失败) {number}   status_code 401：登录失败
     * @apiError    (请求失败) {string}   message     失败信息
     */
    public function tokenLogin()
    {
        if ($member = Member::tokenMember()) {
            return $this->response->array([ 'member' => $member->toArray() ]);
        }

        return $this->response->errorUnauthorized();
    }

    public function resetPassword()
    {

    }
}