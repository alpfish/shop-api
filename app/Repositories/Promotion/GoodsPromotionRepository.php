<?php

namespace App\Repositories\Promotion;

use Exception;
use App\Repositories\Caches\Promotion\PromotionCacheRepository as PromotionCache;


/**
 * 商品促销
 *
 * 范围类型：
 * [单品计算] 满足条件的活动单品有多少件就参加多少次优惠，但一次规则只包含一件单品计算。
 *           如：顾客购买了活动内的[1, 2, 3]号商品，1、3号商品均满足活动条件，则享受两次活动优惠。
 * [一起计算] 满足条件的活动单品一起参加一次优惠，不累加。
 *           如：顾客购买了活动内的[1, 2, 3]号商品，三件商品一起满足活动条件。则享受一次活动优惠。
 *
 * >. 海盗系统是单品计算。
 *
 * >. 本系统支持 单品计算 和 一起计算。默认为单品计算，可在控制器处理一起计算。
 *
 * 系统规则：
 * > 一个SKU只能参加一个单品促销活动
 * > 一个单品促销活动可以有多个规则
 * > 一个单品只享受一个规则的优惠，默认按满足条件最高的规则优惠
 * > 活动可设置是否参加订单促销，默认否
 *
 * 规则类型：
 * 1. 满额减: amount_discount
 * 2. 满额送: amount_give
 * 3. 满件减: number_discount
 * 4. 满件送: number_give
 *
 * 规则字段: rules
 * 1. type - 类型
 * 2. condition - 条件：元/件的数值
 * 3. discount  - 折扣：满减为金额数，满送为赠品sku_id
 * 如：
 * {
 * "1":{"type":"amount_discount","condition":"100","discount":"5"},
 * "2":{"type":"number_discount","condition":"10","discount":"5"},
 * "3":{"type":"amount_give","condition":"200","discount":"1"},
 * "4":{"type":"number_give","condition":"10","discount":"5"}
 * }
 *
 */
class GoodsPromotionRepository
{
    /**
     * 商品促销结算
     *
     * 商品/限时促销固定不可取消。
     * 接收输入条件，返回结算结果，不负责结算范围处理
     * 支持按件/元打折, 最高50%OFF，在后台设置满1元减0.XX元即可。
     * eg: (同件商品) 买一件9折、两件8折、三件6折...
     *
     * @param int   $prom_id 商品促销活动 id
     * @param float $amount  金额（指总额，非单价）
     * @param int   $number  数量
     *
     * @return array $settle [ 'prom_name', 'share_order', 'settle_amount' 'discounted'  'gift' ]
     *                       [ '活动名', '参与订单促销', '结算金额', '已折扣金额', '赠品id']
     * @throws
     * Author AlpFish 2016/9/13
     */
    static public function settlement($prom_id, $amount, $number)
    {
        $settle_init = [
            'prom_name'     => '',
            'share_order'   => true, # 享受订单促销
            'settle_amount' => $amount,
            'discounted'    => 0,
            'gift'       => '', # 不组装赠品信息
        ];
        if (!is_numeric($amount) || $amount < 0) {
            throw new Exception('无效的商品金额');
        }
        if (!(int)$prom_id) {
            return $settle_init;
        }
        // 活动不存在
        if (!$prom = PromotionCache::getGoodsPromById($prom_id)) {
            return $settle_init;
        }
        // 活动时间
        if (!empty( $prom->start_time ) && time() < $prom->start_time) {
            return $settle_init;
        }
        if (!empty( $prom->end_time ) && time() > $prom->end_time) {
            return $settle_init;
        }

        // 规则计算
        $settles = collect()->push($settle_init);
        foreach ($prom->rules as $rule){

            // 初始化
            $settle    = $settle_init;
            $condition = $rule->condition;
            $discount  = $rule->discount;

            switch ($rule->type){
                case 'number_discount': # 满件减
                case 'amount_discount': # 满额减
                {
                    // 支持打折, 最高50%OFF，在后台设置满1元减0.XX元即可。
                    if (( $number >= $condition || $amount >= $condition ) && env('SHOP_PERCENT_DISCOUNT', true) && $discount <= 0.5 && $condition == 1) {
                        $settle[ 'settle_amount' ] = $amount - $amount * $discount;
                        $settle[ 'discounted' ]    = $amount * $discount;
                    } elseif (( $number >= $condition || $amount >= $condition ) && ( $amount - $discount ) >= 0){
                        $settle[ 'settle_amount' ] = $amount - $discount;
                        $settle[ 'discounted' ]    = $discount;
                    }
                    break;
                }
                case 'number_give':# 满件送
                case 'amount_give':# 满额送
                {
                    if ($number >= $condition || $amount >= $condition) {
                        $settle[ 'gift' ] = $discount;
                    }
                }
            }

            if ($settle[ 'discounted' ] || $settle[ 'gift' ]) {
                $settle[ 'prom_name' ]   = $prom->name;
                $settle[ 'share_order' ] = (boolean)$prom->share_order; # 由活动决定，参加了商品促销后是否参加订单促销
                $settles->push($settle);
            }
        }

        // 筛选：优惠力度最大->付款总额最高->有赠品
        $settle = $settles->sortByDesc(function($item){
            return (float)( sprintf("%.2f", $item[ 'discounted' ]) . sprintf("%04d", $item[ 'settle_amount' ]) . $item[ 'gift' ] );
        })->first();

        return $settle;
    }

}