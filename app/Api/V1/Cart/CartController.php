<?php


namespace Api\V1\Cart;

use App\Repositories\Cart\CartRepository as Cart;

class CartController
{
    protected $sku_id;

    // 使用主键提升效率
    protected $cart_id;

    public function __construct()
    {
        $this->sku_id   = app('request')->get('sku_id');
        $this->cart_id  = app('request')->get('cart_id');
        $this->quantity = app('request')->get('quantity');
    }

    public function all()
    {
        return Cart::all();
    }

    /****************************************************************************************
     * 添加购物车商品
     * Alpfish 2016/9/13 13:15
     *
     * @api             {GET} /cart/add ADD
     * @apiName         ADD
     * @apiGroup        Cart
     * @apiVersion      1.0.0
     * @apiPermission   auth
     * @apiDescription  添加购物车商品
     *
     * @apiParam {int} sku_id 商品
     * @apiParam {int} quantity 数量
     *
     * @apiSuccess  (成功响应) {array}    added 所添加的商品
     * @apiError    (失败响应) {number}   status_code 422：参数或值错误，500：服务器错误
     * @apiError    (失败响应) {string}   message     错误信息
     * @apiError    (失败响应) {string}   errors      对应字段的验证错误信息
     */
    public function add()
    {
        $rules   = [
            'sku_id'   => 'required',
            'quantity' => 'required|numeric|min:1',
        ];
        $payload = app('request')->only('sku_id', 'quantity');
        $v       = app('validator')->make($payload, $rules);
        if ($v->fails()) {
            throw new \Dingo\Api\Exception\ValidationHttpException($v->errors());
        }

        $added = Cart::add($this->sku_id, $this->quantity);

        return [ 'added' => $added ];
    }

    /****************************************************************************************
     * 更新购物车商品数量
     * Alpfish 2016/9/13 16:15
     *
     * @api             {GET} /cart/add UPDATE
     * @apiName         UPDATE
     * @apiGroup        Cart
     * @apiVersion      1.0.0
     * @apiPermission   auth
     * @apiDescription  更新购物车商品数量,
     *                  后台不判断库存，在获取购物车时带库存量&&前端判断&&后台结算时判断
     *
     * @apiParam {int} cart_id  购物车id，注意不是sku_id
     * @apiParam {int} quantity 数量
     *
     * @apiSuccess  (成功响应) {boolean}  updated       更新结果(其实状态码 200 即可视为更新成功)
     * @apiError    (失败响应) {number}   status_code   422：参数或值错误，500：服务器错误
     * @apiError    (失败响应) {string}   message       错误信息
     * @apiError    (失败响应) {string}   errors        对应字段的验证错误信息
     */
    public function update()
    {
        $rules   = [
            'cart_id'  => 'required',
            'quantity' => 'required|numeric|min:1',
        ];
        $payload = app('request')->only('cart_id', 'quantity');
        $v       = app('validator')->make($payload, $rules);
        if ($v->fails()) {
            throw new \Dingo\Api\Exception\ValidationHttpException($v->errors());
        }

        return [ 'updated' => Cart::update($this->cart_id, $this->quantity) ];
    }

    /****************************************************************************************
     * 删除购物车商品
     * Alpfish 2016/9/13 16:20
     *
     * @api             {GET} /cart/add DELETE
     * @apiName         DELETE
     * @apiGroup        Cart
     * @apiVersion      1.0.0
     * @apiPermission   auth
     * @apiDescription  删除购物车商品
     *
     * @apiParam {string} cart_id 购物车id，支持多个用','号分隔，注意不是sku_id
     *
     * @apiSuccess  (成功响应) {boolean}  deleted       删除结果, true|数字表示删除成功，fasle|null 表示没有记录被删除
     * @apiError    (失败响应) {number}   status_code   422：参数或值错误，500：服务器错误
     * @apiError    (失败响应) {string}   message       错误信息
     * @apiError    (失败响应) {string}   errors        对应字段的验证错误信息
     */
    public function delete()
    {
        if (!$this->cart_id) {
            throw new \Dingo\Api\Exception\ValidationHttpException([ 'cart_id' => '请求参数不正确' ]);
        }

        return [ 'deleted' => Cart::delete(explode(',', $this->cart_id)) ];
    }

    public function settlement()
    {
        return Cart::settlement([
            [ 'sku_id' => 3, 'quantity' => 5, 'join_prom' => true],
            [ 'sku_id' => 1, 'quantity' => 1, 'join_prom' => true],
            [ 'sku_id' => 4, 'quantity' => 8, 'join_prom' => true],
            [ 'sku_id' => 5, 'quantity' => 1, 'join_prom' => true],
            [ 'sku_id' => 8, 'quantity' => 1, 'join_prom' => true],
            [ 'sku_id' => 9, 'quantity' => 10, 'join_prom' => true],
            [ 'sku_id' => 2, 'quantity' => 10, 'join_prom' => true],
            [ 'sku_id' => 6, 'quantity' => 1, 'join_prom' => true],
        ], null, null, ['sku_id']);
    }

}