<?php

namespace App\Repositories\Promotion;

use Exception;
use App\Models\Promotion\TimePromotion\TimePromotion;

class TimePromotionRepository
{
    static protected $proms = [ ];

    /**
     * 限时促销结算
     *
     * @param int    $prom_id    活动id
     * @param int    $sku_id     商品id
     * @param number $orig_price 商品原价（活动无效时返回）
     *
     * @return array $settle = ['prom_name', 'settle_price'] - ['活动名称', '结算单价']
     * @throws
     * Author AlpFish 2016/9/14
     */
    static public function settlement($prom_id, $sku_id, $orig_price)
    {
        $settle = [
            'prom_name'    => '',
            'settle_price' => $orig_price,
        ];

        if (!is_numeric($orig_price) or $orig_price < 0) {
            throw new Exception('无效的原价.');
        }
        if (!is_numeric($prom_id) || $prom_id < 1) {
            return $settle;
        }
        if (!is_numeric($sku_id) || $sku_id < 1) {
            return $settle;
        }
        if (!isset( self::$proms[ $prom_id ] )) {
            self::$proms[ $prom_id ] = TimePromotion::find($prom_id);
        }
        if (!$prom = self::$proms[ $prom_id ]) {
            return $settle;
        }
        // 活动时间
        if (!empty( $prom->start_time ) && time() < $prom->start_time) {
            return $settle;
        }
        if (!empty( $prom->end_time ) && time() > $prom->end_time) {
            return $settle;
        }

        if (isset( $prom->sku_info[ $sku_id ] ) && $prom->sku_info[ $sku_id ] > 0) {
            $settle[ 'prom_name' ]    = $prom->name;
            $settle[ 'settle_price' ] = $prom->sku_info[ $sku_id ];
        }

        return $settle;
    }
}