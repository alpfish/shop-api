<?php
namespace App\Repositories\Goods\Category;

use App\Repositories\Caches\Goods\Goods\GoodsCacheRepository;
use App\Repositories\Caches\Goods\Category\CategoryCacheRepository;

class CategoryRepository
{

    /**
     * 使用 类目id 获取搜索列表下的商品
     *
     * @param int $cid 类目id
     *
     * @return \Illuminate\Database\Eloquent\Collection
     *
     * @author AlpFish 2016/8/15 17:06
     */
    public static function getGoodsOfSearchListWithCategoryId($cid, $page = 1, $perPage = 240)
    {
        // [省]判断类目存在 （降低性能，情况很少出现，出现搜索结果也为空，故不作判断）

        // 获取 叶类目ids
        $cids = json_decode(CategoryCacheRepository::getCategoryEndIdsWithCategoryid($cid));

        // 获取 商品ids 和 search_score
        $idsAndSort = [];
        foreach ($cids as $cid){
            $idsAndSort = array_merge($idsAndSort, (array)json_decode(CategoryCacheRepository::getGoodsIdsAndSearchScoreWithCategoryId($cid)));
        }
        // 过滤 商品ids
        $filterIds = collect($idsAndSort)->sortByDesc('search_score')->forPage($page, $perPage);

        $searchedGoods = collect();
        foreach ($filterIds as $item){
            $searchedGoods->push(json_decode(GoodsCacheRepository::getGoodsItemOfSearchListWithGoodsId($item->id)));
        }

        return $searchedGoods;
    }

    /**
     * 获取类目模型所有数据
     *
     * @return \Illuminate\Database\Eloquent\Collection
     *
     * @author AlpFish 2016/8/15 17:25
     */
    public static function getCategoryAllData()
    {
        return collect(json_decode(CategoryCacheRepository::getCategoryAllItems()));
    }
    
}