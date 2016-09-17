<?php

namespace App\Models\Cart;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Cart\Relation\CartRelation;

class Cart extends  Model
{
    use CartRelation;

    protected $table = 'cart';

    protected $hidden = [];

    /**
     * 禁用时间戳
     *
     * 禁用 Lumen 自带的 created_at 和 updated_at 两个时间戳字段，
     * 表中没有这两个字段，禁用后插入/更新记录才能成功。
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * 模型引导
     * The "booting" method of the model.
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        /**
         * 全局查询作用域
         *
         * 默认获取已认证用户的记录:
         *
         * Cart::all(); 生成的SQL语句为： select * from `cart` where `buyer_id` = '123***'
         *
         * 查询时移除方法：
         * Cart::withoutGlobalScope('auth_member')->get();
        */
        static::addGlobalScope('auth_member', function(Builder $builder) {
            $builder->whereBuyerId(auth_member()->id);
        });
    }

}