<?php

namespace App\Repositories\Promotion;

use App\Repositories\Caches\Promotion\PromotionCacheRepository as PromotionCache;
use Exception;

class OrderPromotionRepository
{
    /**
     * 订单促销结算(要返回订单满足条件的促销条目)
     *
     * 商品/限时促销固定不可取消。
     * 已参加商品促销后不再参加订单促销中的打折促销，可参加订单促销中的满减满送。
     * 包邮促销为全店促销，与其它促销同时享受。
     * 支持打折(已参加促销的商品除外)，最高50%OFF，在后台设置满1元减0.XX元即可。
     *
     * @param float $amount     参加订单促销的金额
     * @param int   $checked_id 被选中的订单活动id（如果没有，则系统自动选择订单满足条件最高的活动）
     * @param int   $sku_amount 订单SKU总额
     *
     * @return Collection [ 'prom_id', 'prom_name', 'discounted', 'gift', 'checked' '(freight)', ]
     *                    [ '活动id', '活动名称', '优惠', '赠品id', '是否选中(只有一个被选中)', '邮费(免邮时存在)']
     * @throws
     * Author AlpFish 2016/9/14
     */
    static public function settlement($amount, $checked_id = 0, $sku_amount = null)
    {
        if (!is_numeric($amount) || $amount < 0) {
            throw new Exception('无效的订单金额。');
        }

        $init[ 'prom_id' ]    = 0;
        $init[ 'prom_name' ]  = '';
        $init[ 'discounted' ] = 0;
        $init[ 'gift' ]       = '';
        $init[ 'checked' ]    = false;
        $init[ 'condition' ]  = 0;

        // 获取所有促销活动
        $items = PromotionCache::getAllOrderProm();
        $proms = collect();
        foreach ($items as $item){
            $prom = $init;
            // 活动时间
            if (!empty( $item->start_time ) && time() < $item->start_time) {
                continue;
            }
            if (!empty( $item->end_time ) && time() > $item->end_time) {
                continue;
            }

            if ($amount >= $item->price) {
                $prom[ 'prom_id' ]   = $item->id;
                $prom[ 'prom_name' ] = $item->name;
                $prom[ 'condition' ] = $item->price;
                switch ($item->type){
                    case '0' : { # 满额减
                        // 订单打折，最高50%OFF，在后台设置满1元减0.XX元即可。
                        if (env('SHOP_PERCENT_DISCOUNT', true) && $item->discount <= 0.5 && $item->price == 1) {
                            $prom[ 'discounted' ] = $amount * $item->discount;
                        } elseif ($sku_amount >= $item->price && ( $amount - $item->discount ) >= 0){
                            $prom[ 'discounted' ] = $item->discount;
                        }
                        break;
                    }
                    case '1' : { # 满免邮
                        if ($sku_amount >= $item->price) {
                            $prom[ 'freight' ] = 0;
                        }
                        break;
                    }
                    case '2' : { # 满就送
                        if ($sku_amount >= $item->price) {
                            $prom[ 'gift' ] = $item->discount;
                        }
                    }
                }
            }
            if ($prom[ 'discounted' ] || $prom[ 'gift' ] || isset( $prom[ 'freight' ] )) {
                $proms->push($prom);
            }
        }

        if ($proms->count() < 1) {
            $proms->push($init);
        }

        // 确定 $checked_id 有效
        $checked = false;
        if ($checked_id) {
            $res     = $proms->search(function($item) use ($checked_id){
                return $item[ 'prom_id' ] == $checked_id;
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
}