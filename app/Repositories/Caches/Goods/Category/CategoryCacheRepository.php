<?php

namespace App\Repositories\Caches\Goods\Category;

use App\Models\Goods\Category\Category;

class CategoryCacheRepository
{
    /**
     * 使用 类目id 获取: 其下所有 商品id 和 search_score
     *
     * @param int $cid
     *
     * @return string JSON
     *
     * @author AlpFish 2016/8/13 19:55
     */
    public static function getGoodsIdsAndSearchScoreWithCategoryId($cid)
    {
        $key = GOODS_IDS_AND_SEARCH_SCORE_WITH_CATEGORY_ID_CACHE.$cid;
        if (! app('cache')->has($key)) {
            $result = null;
            if ($category = Category::find($cid)) {
                $goods = $category->goods()->get();
                // search_score 是 Goods 模型的一个访问器属性，不能通过查询构建器 lists() 获取其值，可以通过集合获取
                $result = $goods->map(function($item) {
                    return array(
                        'id' => $item->id,
                        'search_score' => $item->search_score
                    );
                });
            };
            $result = json_encode($result);

            $time = GOODS_IDS_AND_SEARCH_SCORE_WITH_CATEGORY_ID_CACHE_TIME;
            app('cache')->put($key, $result, $time);
        }

        return app('cache')->get($key);
    }
    
    /**
     * 使用 类目id 获取：其下的所有端点（叶）类目ids
     *
     * @param int $cid 类目id
     *
     * @return string JSON
     *
     * @author AlpFish 2016/8/13 12:50
     */
    public static function getCategoryEndIdsWithCategoryid($cid)
    {
        $key = CATEGORY_END_IDS_WITH_CATEGORY_ID_CACHE . $cid;
        if (! app('cache')->has($key)) {

            // 集合处理缓存数据 （较数组处理方法性能丢失不大可以忽略，但集合拥有更便捷直观的管道方法，所以推荐缓存使用JSON, 操作使用集合）
            $all = collect(json_decode(self::getCategoryAllItems()))->transform(function($item){
                return $item = ['id' => $item->id, 'parent_id' => $item->parent_id];
            });

            $pids = $all->pluck('parent_id')->unique();
            if (! $pids->contains($cid)) {
                $ends = json_encode([(int) $cid]);
            } elseif ($cid == 0){
                $ends = $all->pluck('id')->diff($pids)->flatten()->toJson();
            } else{
                $ends = $all->whereLoose('parent_id', $cid)->pluck('id')->toJson();
            }

            // 数组处理缓存数据
            /*
            $allCateData = json_decode(self::getCategoryAllItems());
            $idsAndPids = array_map(function($item){
                return array('id'=>$item->id, 'parent_id' => $item->parent_id);
            }, $allCateData);

            $pids = array_values(array_unique(array_column($idsAndPids, 'parent_id')));
            if (! in_array($cid, $pids)) {
                // cid 是叶目录
                $ends = [(int) $cid];
            } elseif ($cid == 0){
                $ends = array_diff(array_values(array_column($idsAndPids, 'id')), $pids);
            } else{
                $ends = array_values(array_column(array_filter($idsAndPids, function($item) use($cid) {
                    return $item['parent_id'] == $cid;
                }), 'id'));
            }
            */

            $time = CATEGORY_END_IDS_WITH_CATEGORY_ID_CACHE_TIME;
            app('cache')->put($key, $ends, $time);
        }

        return app('cache')->get($key);
    }

    /**
     * 获取类目所有记录
     *
     * @return string JSON
     *
     * @author AlpFish 2016/8/13 13:21
     */
    public static function getCategoryAllItems()
    {
        $key = CATEGORY_ALL_ITEMS_CACHE;
        if (! app('cache')->has($key)) {
            // 使用 JSON 数据代替原来的 Collection 数据缓存，数据容量下降7-8倍，2000并发（循环）在 Redis 缓存下性能提升5倍以上!
            $value = Category::all()->toJson();

            $time = CATEGORY_ALL_ITEMS_CACHE_TIME;
            app('cache')->put($key, $value, $time);
        }

        return app('cache')->get($key);
    }
}