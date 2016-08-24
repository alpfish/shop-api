<?php
namespace App\Repositories\Goods\Category;

use App\Repositories\Caches\Goods\Goods\GoodsCacheRepository;
use App\Repositories\Caches\Goods\Category\CategoryCacheRepository;

class CategoryRepository
{

  /**
   * 获取类目树
   *
   * @return array
   *
   * @author AlpFish 2016/8/19 7:33
   */
  public static function getTreeOfMible()
  {
    return CategoryCacheRepository::getTreeOfMobile();
  }

  /**
   * 获取类目下的商品
   *
   * @return array
   *
   * @author AlpFish 2016/8/21 18:21
   */
  public static function getGoodsIdsByCid($cid)
  {
    return CategoryCacheRepository::getGoodsIdsByCid($cid);
  }

}