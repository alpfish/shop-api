<?php
namespace App\Repositories\Goods\Category;

use App\Repositories\Caches\Goods\Goods\GoodsCacheRepository;
use App\Repositories\Caches\Goods\Category\CategoryCacheRepository;

class CategoryRepository
{

  /**
   * 使用 类目id 获取搜索列表下的商品
   *
   * @param int $cid     类目id
   * @param int $page    页数
   * @param int $perPage 每页数
   *
   * @return \Illuminate\Database\Eloquent\Collection
   *
   * @author AlpFish 2016/8/15 17:06
   */
  public static function getGoodsOfSearchListWithCategoryId($cid, $page, $perPage)
  {
    // [省]判断类目存在 （降低性能，情况很少出现，出现搜索结果也为空，故不作判断）

    // 获取 叶类目ids
    $cids = json_decode(CategoryCacheRepository::getEndCategoryIdsWithCategoryid($cid));

    // 获取 商品ids 和 search_score
    $idsAndSort = [];
    foreach ($cids as $cid){
      $idsAndSort = array_merge($idsAndSort, (array)json_decode(CategoryCacheRepository::getGoodsIdsAndSortWithEndCid($cid)));
    }
    // 过滤 商品ids
    $filterIds = collect($idsAndSort)->sortByDesc('sort')->forPage($page, $perPage);

    $searchedGoods = collect();
    foreach ($filterIds as $item){
      $searchedGoods->push(json_decode(GoodsCacheRepository::getGoodsItemOfSearchListWithGoodsId($item->id)));
    }

    return array (
      'total' => count($idsAndSort),
      'page' => $page,
      'per_page' => $perPage,
      'goods' => $searchedGoods
    );
  }

  /**
   * 获取类目树
   *
   * @return array
   *
   * @author AlpFish 2016/8/19 7:33
   */
  public static function getCategoryTreeOfMible()
  {
    return json_decode(CategoryCacheRepository::getCategoryTreeOfMobile());
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
    return json_decode(CategoryCacheRepository::getGoodsIdsByCid($cid));
  }

}