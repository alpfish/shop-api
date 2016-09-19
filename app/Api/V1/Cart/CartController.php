<?php


namespace Api\V1\Cart;

use App\Repositories\Cart\CartRepository as Cart;

class CartController
{
    public function all()
    {
        return Cart::settlement();
    }

    /****************************************************************************************
     * 添加购物车商品
     * Alpfish 2016/9/13 13:15
     *
     * @api             {GET} cart/add add
     * @apiName         ADD
     * @apiGroup        Cart
     * @apiVersion      1.0.0
     * @apiPermission   auth
     * @apiDescription  添加购物车商品，商品存在时直接更新数量为 buy_nums ，不自增计算，自增请在前台进行
     *
     * @apiParam {int} sku_id 商品
     * @apiParam {int} buy_nums 数量
     *
     * @apiSuccess  (成功响应) {array}    added 所添加的商品
     * @apiSuccess  (成功响应) {array}    xxx 结算数据，见结算
     * @apiError    (失败响应) {number}   status_code 422：参数或值错误，500：服务器错误
     * @apiError    (失败响应) {string}   message     错误信息
     * @apiError    (失败响应) {string}   errors      对应字段的验证错误信息
     */
    public function add()
    {
        $rules   = [
            'sku_id'   => 'required',
            'buy_nums' => 'required|numeric|min:1',
        ];
        $payload = app('request')->only('sku_id', 'buy_nums');
        $v       = app('validator')->make($payload, $rules);
        if ($v->fails()) {
            throw new \Dingo\Api\Exception\ValidationHttpException($v->errors());
        }
        $sku_id   = app('request')->get('sku_id');
        $buy_nums = app('request')->get('buy_nums');

        return array_merge(
            [ 'added' => Cart::add($sku_id, $buy_nums) ],
            (array)Cart::settlement()
        ); # 合并结算数据
    }

    /****************************************************************************************
     * 更新购物车商品数量
     * Alpfish 2016/9/13 16:15
     *
     * @api             {GET} cart/update update
     * @apiName         UPDATE
     * @apiGroup        Cart
     * @apiVersion      1.0.0
     * @apiPermission   auth
     * @apiDescription  更新购物车商品数量,
     *                  不检查库存，在返回结算数据时带库存量&&前端判断&&后台结算时判断
     *
     * @apiParam {int}      id       购物车id
     * @apiParam {int}      buy_nums 购买数量
     * @apiParam {string}   [settle_ids] 参与结算的ids，使用','分隔，无此参数则结算所有购物车商品
     *
     * @apiSuccess  (成功响应) {boolean}  updated       更新结果(其实状态码 200 即可视为更新成功)
     * @apiSuccess  (成功响应) {array}    xxx           结算数据，见结算
     * @apiError    (失败响应) {number}   status_code   422：参数或值错误，500：服务器错误
     * @apiError    (失败响应) {string}   message       错误信息
     * @apiError    (失败响应) {string}   errors        对应字段的验证错误信息
     */
    public function update()
    {
        $rules   = [
            'id'       => 'required',
            'buy_nums' => 'required|numeric|min:1',
        ];
        $payload = app('request')->only('id', 'buy_nums');
        $v       = app('validator')->make($payload, $rules);
        if ($v->fails()) {
            throw new \Dingo\Api\Exception\ValidationHttpException($v->errors());
        }
        $id         = app('request')->get('id');
        $buy_nums   = app('request')->get('buy_nums');
        $settle_ids = app('request')->get('settle_ids') ? explode(',', app('request')->get('settle_ids')) : [ ];

        return array_merge(
            [ 'updated' => Cart::update($id, $buy_nums) ],
            (array)Cart::settlement($settle_ids)
        ); # 合并结算数据
    }

    /****************************************************************************************
     * 删除购物车商品
     * Alpfish 2016/9/13 16:20
     *
     * @api             {GET} cart/delete delete
     * @apiName         DELETE
     * @apiGroup        Cart
     * @apiVersion      1.0.0
     * @apiPermission   auth
     * @apiDescription  删除购物车商品
     *
     * @apiParam {string} id 购物车id，支持多个用','号分隔，注意不是sku_id
     * @apiParam {string}   [settle_ids] 参与结算的ids，使用','分隔，无此参数则结算所有购物车商品
     *
     * @apiSuccess  (成功响应) {boolean}  deleted       删除结果, true|数字表示删除成功，fasle|null 表示没有记录被删除
     * @apiSuccess  (成功响应) {array}    xxx           结算数据，见结算
     * @apiError    (失败响应) {number}   status_code   422：参数或值错误，500：服务器错误
     * @apiError    (失败响应) {string}   message       错误信息
     * @apiError    (失败响应) {string}   errors        对应字段的验证错误信息
     */
    public function delete()
    {
        $id = app('request')->get('id');
        if (!is_numeric($id) || $id < 1) {
            throw new \Dingo\Api\Exception\ValidationHttpException([ 'id' => '请求参数不正确' ]);
        }
        $settle_ids = app('request')->get('settle_ids') ? explode(',', app('request')->get('settle_ids')) : [ ];

        return array_merge(
            [ 'deleted' => Cart::delete(explode(',', $id)) ],
            (array)Cart::settlement($settle_ids)
        );
    }

    /****************************************************************************************
     *
     * 购物车结算
     *
     * Alpfish 2016/9/19 10:38
     *
     * @api             {GET} cart/settlement settlement
     * @apiName         SETTLEMENT
     * @apiGroup        Cart
     * @apiVersion      1.0.0
     * @apiPermission   auth
     * @apiDescription  对所给购物车ids进行结算，ids为空则结算购物车内所有商品。
     * 只有购物车内有商品才返回结算数据，购物车为空，响应数据则为空。结算数据包括三个数组字段:
     * 1.settlement(结算，一维) 2.order_proms(订单促销，二维) 3.cart_items(购物车条目，二维)
     *
     * @apiParam {string} ids 购物车ids，支持多个用','号分隔，注意不是sku_id
     *
     * @apiSuccess  (成功：settlement)  {float}    sku_amount      商品总额
     * @apiSuccess  (成功：settlement)  {float}    settle_amount   结算总额 = 商品总额 - 优惠总额 + 税收
     * @apiSuccess  (成功：settlement)  {float}    discounted      优惠总额 = 商品优惠 + 订单优惠
     * @apiSuccess  (成功：settlement)  {float}    invoice_tax     税费
     * @apiSuccess  (成功：settlement)  {float}    freight         运费
     * @apiSuccess  (成功：settlement)  {int}      count           购买数量
     * @apiSuccess  (成功：order_proms) {int}      prom_id     订单促销id
     * @apiSuccess  (成功：order_proms) {string}   prom_name   订单促销活动名称
     * @apiSuccess  (成功：order_proms) {float}    discounted  订单促销优惠金额
     * @apiSuccess  (成功：order_proms) {array}    gift        订单促销赠品
     * @apiSuccess  (成功：order_proms) {boolean}  checked     是否选择
     * @apiSuccess  (成功：cart_items)  {int}      id             购物车id
     * @apiSuccess  (成功：cart_items)  {int}      sku_id         sku_id
     * @apiSuccess  (成功：cart_items)  {int}      buy_nums       购买数量
     * @apiSuccess  (成功：cart_items)  {int}      sku_nums       库存数量
     * @apiSuccess  (成功：cart_items)  {string}   sku_name       商品名称
     * @apiSuccess  (成功：cart_items)  {array}    sku_spec       商品规格
     * @apiSuccess  (成功：cart_items)  {string}   sku_thumb      缩略图
     * @apiSuccess  (成功：cart_items)  {boolean}  is_onsale      是否上架
     * @apiSuccess  (成功：cart_items)  {float}    shop_price     店铺销售价
     * @apiSuccess  (成功：cart_items)  {float}    prom_price     促销价
     * @apiSuccess  (成功：cart_items)  {int}      prom_id        促销id
     * @apiSuccess  (成功：cart_items)  {string}   prom_type      促销类型
     * @apiSuccess  (成功：cart_items)  {string}   prom_name      促销活动名称
     * @apiSuccess  (成功：cart_items)  {float}    settle_amount  单品结算总额
     * @apiSuccess  (成功：cart_items)  {float}    discounted     优惠金额
     * @apiSuccess  (成功：cart_items)  {array}    gift           赠品
     * @apiError    (失败响应)     {number}   status_code 422：参数或值错误，500：服务器错误
     * @apiError    (失败响应)     {string}   message     错误信息
     * @apiError    (失败响应)     {string}   errors      对应字段的验证错误信息
     */
    public function settlement()
    {
        if (!app('request')->has('ids')) {
            throw new \Dingo\Api\Exception\ValidationHttpException([ 'ids' => '请求参数不正确' ]);
        }
        $ids = explode(',', app('request')->get('ids'));

        return Cart::settlement($ids, null, null);
    }

}