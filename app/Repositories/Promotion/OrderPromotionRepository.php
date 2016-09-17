<?php

namespace App\Repositories\Promotion;

use App\Models\Promotion\OrderPromotion\OrderPromotion;
use Exception;

class OrderPromotionRepository
{
    /**
     * 订单促销结算(要返回订单满足条件的促销条目)
     *
     * 多个活动时支持系统自动选择，前台顾客可自由选择。
     * 支持打折(已参加促销的商品除外)，最高50%OFF，在后台设置满1元减0.XX元即可。
     *
     * @param float $amount     金额
     * @param int   $checked_id 被选中的订单活动id（如果没有，则系统自动选择订单满足条件最高的活动）
     *
     * @return Collection [ 'prom_id', 'prom_name', 'discounted', 'gift', 'freight', 'checked' ]
     *                    [ '活动id', '活动名称', '优惠', '赠品id', '邮费(免邮时存在)', '是否选中(只有一个被选中)']
     * @throws
     * Author AlpFish 2016/9/14
     */
    static public function settlement($amount, $checked_id = 0)
    {
        if (!is_numeric($amount) || $amount < 0) {
            throw new Exception('无效的订单金额。');
        }

        // 获取所有促销活动
        $items = OrderPromotion::all();
        $proms = collect();
        foreach ($items as $item){
            $proms->push(self::promotionCompute($item, $amount));
        }

        // 过滤无效活动
        if ($proms->sum('prom_id') > 0) {
            $proms = $proms->filter(function($item){
                return $item[ 'prom_id' ] > 0;
            });
        } else{
            $proms = collect([ $proms->first() ]);
        }

        // 确定 $checked_id 有效
        $checked = false;
        if ($checked_id) {
            $res = $proms->search(function($item) use($checked_id){
                return $item['prom_id'] == $checked_id;
            });
            $checked = $res === false ? false : true;
        }
        // 为前端添加 checked 选项
        if ($checked) {
            foreach ($proms as $key => $value){
                if ($value[ 'prom_id' ] == $checked_id) {
                    $value[ 'checked' ] = true;
                    $proms->put($key, $value);
                    break;
                }
            }
        } else{ # 系统选择 优惠力度最大or优惠条件高 的活动
            $proms             = $proms->sortByDesc(function($item){
                return (float)( sprintf("%.2f", $item[ 'discounted' ]) . sprintf("%05d", $item[ 'condition' ]) );
            })->values();
            $item              = $proms->first();
            $item[ 'checked' ] = true;
            $proms->put(0, $item);
        }

        // 删除优惠条件属性
        $proms = $proms->map(function($item){
            return collect($item)->except('condition')->toArray();
        });

        return $proms;
    }

    /**
     * 订单优惠计算
     *
     * @param Model $prom   优惠记录
     * @param float $amount 参加订单促销的金额
     *
     * @return array
     * @throws
     *
     * Author AlpFish 2016/9/15
     */
    static protected function promotionCompute($prom, $amount)
    {
        if (!$prom instanceof OrderPromotion) {
            throw new Exception('$prom 参数不是一个有效的模型实例。');
        }
        $settle[ 'prom_id' ]    = 0;
        $settle[ 'prom_name' ]  = '';
        $settle[ 'discounted' ] = 0;
        $settle[ 'gift' ]       = '';
        $settle[ 'checked' ]    = false;
        $settle[ 'condition' ]  = 0;
        // 活动时间
        if (!empty( $prom->start_time ) && time() < $prom->start_time) {
            return $settle;
        }
        if (!empty( $prom->end_time ) && time() > $prom->end_time) {
            return $settle;
        }

        if ($amount >= $prom->price) {
            $settle[ 'prom_id' ]   = $prom->id;
            $settle[ 'prom_name' ] = $prom->name;
            $settle[ 'condition' ] = $prom->price;
            switch ($prom->type){
                case '0' : { # 满额减
                    // 支持打折，最高50%OFF，在后台设置满1元减0.XX元即可。
                    if (env('SHOP_PERCENT_DISCOUNT', true) && $prom->discount <= 0.5 && $prom->price == 1) {
                        $settle[ 'discounted' ] = $amount * $prom->discount;
                    } elseif ($amount >= $prom->price && ( $amount - $prom->discount ) >= 0){
                        $settle[ 'discounted' ] = $prom->discount;
                    }
                    break;
                }
                case '1' : { # 满免邮
                    if ($amount >= $prom->price) {
                        $settle[ 'freight' ] = 0;
                    }
                    break;
                }
                case '2' : { # 满就送
                    if ($amount >= $prom->price) {
                        $settle[ 'gift' ] = $prom->discount;
                    }
                }
            }
        }

        return $settle;
    }
}