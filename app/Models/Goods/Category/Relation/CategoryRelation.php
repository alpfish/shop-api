<?php


namespace App\Models\Goods\Category\Relation;


use App\Models\Goods\Goods\Goods;

trait CategoryRelation
{
    /**
     * 获取类目下的商品
     *
     * @return Goods
     */
    public function goods()
    {
        return $this->hasMany('App\Models\Goods\Goods\Goods', 'catid', 'id');
    }

}