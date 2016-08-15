<?php


namespace App\Models\Goods\Sku\Relation;

trait SkuRelation
{
    /**
     * 获取 sku 所属的商品
     *
     * @return \App\Models\Goods\Goods\Goods
     */
    public function goods()
    {
        return $this->belongsTo('App\Models\Goods\Goods\Goods', 'spu_id', 'id');
    }

    /**
     * 获取 sku 下的商品促销
     *
     * @return \App\Models\Promotion\GoodsPromotion\GoodsPromotion
     */
    public function goods_promotion()
    {
        return $this->belongsTo('App\Models\Promotion\GoodsPromotion\GoodsPromotion', 'prom_id', 'id');
    }

    /**
     * 获取 sku 下的限时促销
     *
     * @return \App\Models\Promotion\TimePromotion\TimePromotion
     */
    public function time_promotion()
    {
        return $this->belongsTo('App\Models\Promotion\TimePromotion\TimePromotion', 'prom_id', 'id');
    }

}