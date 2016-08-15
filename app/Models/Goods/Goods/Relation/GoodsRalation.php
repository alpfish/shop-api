<?php
namespace App\Models\Goods\Goods\Relation;

use App\Models\Promotion\GoodsPromotion\GoodsPromotion;
use App\Models\Goods\Sku\Sku;
trait GoodsRalation
{
    /**
     * 获取商品下的Sku
     *
     * @return App\Models\Goods\Sku\Sku
     */
    public function sku()
    {
        return $this->hasMany('App\Models\Goods\Sku\Sku', 'spu_id', 'id');
    }

    /**
     * 获取商品对应的类目
     *
     * @return App\Models\Goods\Category\Category
     */
    public function category()
    {
        return $this->belongsTo('App\Models\Goods\Category\Category', 'cateid', 'id');
    }

}