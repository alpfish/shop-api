<?php
namespace App\Repositories\Goods\Goods;

use App\Models\Goods\Goods\Goods;
use App\Repositories\Caches\Goods\Goods\GoodsCacheRepository as GoodsCache;

class GoodsRepository
{
  public static function GoodsIdsOrderBy($ids)
  {
    usort($ids, function($a, $b){
      $a = GoodsCache::getGoodsCell($a, 'sort');
      $b = GoodsCache::getGoodsCell($b, 'sort');
      echo $a.'-'.$b.'<br>';
      return ($a > $b) ? -1 : 1;
    });

    ddd($ids);

    return $ids;
  }

    /**
     * 获取商品元数据
     *
     * @param int    $id    主键
     * @param string $field 字段名
     *
     * @return mixed
     *
     * @author AlpFish 2016/8/21 23:10
     */
    public static function getGoodsCell($id, $field)
    {
        return GoodsCache::getGoodsCell($id, $field);
    }
}