<?php

namespace App\Models\Cart\Relation;


trait CartRelation
{
    /**
     * 购物车 : sku
     *
     * @return \App\Models\Goods\Sku\Sku
     */
    public function sku()
    {
        // 关系有点乱，但从外键 sku_id 判断， sku 为主表， 所以用 belongTo
        return $this->belongsTo('App\Models\Goods\Sku\Sku', 'sku_id', 'sku_id');
    }
}