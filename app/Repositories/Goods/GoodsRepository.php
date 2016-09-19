<?php
namespace App\Repositories\Goods;

use App\Models\Goods\Goods\Goods;
use App\Repositories\Caches\Goods\GoodsCacheRepository as GoodsCache;

class GoodsRepository
{
   /**
   * 获取元数据
   *
   * 可以获取数据库表原型字段和模型中使用get()方法自定义的字段数据，
   * 但使用缓存仓库中的计算方法得出的字段数据不要通过该方法获取，必须用相关方法获取，
   * 因为获取时有可能还未添加该字段到缓存中，会返回空。
   *
   * @param int          $id     主键
   * @param string|array $fields 字段名
   *
   * @return string|array 一个字段则直接返回字段值，多个值返回字段名 => 字段值的数组格式数据。
   *
   * @author AlpFish 2016/8/22 21:10
   */
  public static function cell($id, $fields)
  {
    return GoodsCache::cell($id, $fields);
  }

  /**
   * 排序所给商品ids
   *
   * @param array  $ids
   * @param string $field = sort 排序字段: sales, price, sort
   * @param string $order = asc  排序顺序
   *
   * @return array $ids
   *
   * @author AlpFish 2016/8/23 20:10
   */
  public static function sortByIds($ids = array (), $field = 'sort', $order = 'asc')
  {
    return GoodsCache::sortByIds($ids, $field, $order);
  }
}