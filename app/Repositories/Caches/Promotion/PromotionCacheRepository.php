<?php

namespace App\Repositories\Caches\Promotion;

use App\Repositories\Caches\RedisKeys as Keys;
use App\Models\Promotion\OrderPromotion\OrderPromotion;
use App\Models\Promotion\GoodsPromotion\GoodsPromotion;
use App\Models\Promotion\TimePromotion\TimePromotion;

class PromotionCacheRepository
{
    /**
     * 获取所有订单促销模型
     *
     * @return object json_decode
     *
     * Author AlpFish 2016/9/18
     */
    static public function getAllOrderProm()
    {
        $key = Keys::PROMOTION_ORDER_STRS_ALL['key'];
        if (!$data = app('cache')->get($key)) {
            $data = OrderPromotion::all()->toJson();
            $time = Keys::PROMOTION_ORDER_STRS_ALL['time'];
            app('cache')->put($key, $data, $time);
        }

        return json_decode($data);
    }

    /**
     * 根据ID获取商品促销
     *
     * @param int $prom_id
     *
     * @return object json_decode
     *
     * Author AlpFish 2016/9/18
     */
    static public function getGoodsPromById($prom_id)
    {
        $key = sprintf(Keys::PROMOTION_GOODS_STRS_ID['key'], $prom_id);
        if (!$data = app('cache')->get($key)) {
            $data = GoodsPromotion::find($prom_id)->toJson();
            $time = Keys::PROMOTION_GOODS_STRS_ID['time'];
            app('cache')->put($key, $data, $time);
        }

        return json_decode($data);
    }

    /**
     * 根据ID获取限时促销
     *
     * @param int $prom_id
     *
     * @return object json_decode
     *
     * Author AlpFish 2016/9/18
     */
    static public function getTimePromById($prom_id)
    {
        $key = sprintf(Keys::PROMOTION_TIME_STRS_ID['key'], $prom_id);
        if (!$data = app('cache')->get($key)) {
            $data = TimePromotion::find($prom_id)->toJson();
            $time = Keys::PROMOTION_TIME_STRS_ID['time'];
            app('cache')->put($key, $data, $time);
        }

        return json_decode($data);
    }
}