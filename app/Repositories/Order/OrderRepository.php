<?php


namespace App\Repositories\Order;


class OrderRepository
{
    /**
     * 创建订单
     *
     * @param array $cart_items 购物车条目
     * @param array $fields 订单字段
     *
     * @return mixed
     *
     * Author AlpFish 2016/9/14
     */
    static public function create(array $cart_items, $fields)
    {
        // 入口验证

        // 创建保护：判断是否可以购买: 上架 库存 限量 用户可购买状态 ...

        // 商品优惠

        // 订单优惠

        // 其他优惠 (优惠券，红包，积分，...)

        // 字段组装

        // 创建订单

        // 创建子订单

        // 购物车删除
    }
}