<?php


namespace App\Models\Goods\Sku\Attribute;


trait SkuAttribute
{
    public function getThumbAttribute()
    {   // TODO 缓存
        if (!$this->attributes['thumb']) {
            return app('db')->table('goods_spu')->first()->thumb;
        } else {
            return $this->attributes['thumb'];
        }
    }
}