<?php


namespace App\Models\Member\Member\Relation;


trait MemberRelation
{
    /**
     * 会员 : 购物车关系
     *
     * @return \App\Models\Cart\Cart
     */
    public function cart()
    {
        // 本是一对一关系：一个会员拥有一个购物车
        // 但购物车表结构为一条记录存储一个sku_id, 变成一个会员的购物车由多条记录构成。正确否 ？
        $this->hasMany('App\Models\Cart\Cart', 'buyer_id');
    }
}