<?php

namespace App\Repositories\Caches\Goods\Category;

use App\Models\Goods\Category\Category;

class CategoryCacheRepository
{
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
      //            $value = Category::get([
      //                'id',
      //                'parent_id',
      //                'name',
      //                'sort',
      //                'img',
      //                'url',
      //                'cid',
      //                'pid'
      //            ])->toJson();

      $value = Category::all()->toJson();

      $time = CATEGORY_ALL_ITEMS_CACHE_TIME;
      app('cache')->put($key, $value, $time);
    }

    return app('cache')->get($key);
  }

  /**
   * 获取类目树型结构
   *
   * @return strin JSON
   *
   * @author AlpFish 2016/8/19 7:33
   */
  public static function getCategoryTreeOfMobile()
  {
    $key = CATEGORY_TREE_OF_MOBILE_CACHE;

    if (! app('cache')->has($key)) {
      $tree = collect(json_decode(self::getCategoryAllItems()))
        ->filter(function($parent){
          return $parent->parent_id == 0 && $parent->status;
        })
        ->map(function($parent){
          return [
            'cid' => $parent->id,
            'pid' => $parent->parent_id,
            'name' => $parent->name,
            'sort' => $parent->sort,
            'img' => $parent->img,
            'url' => $parent->url,
            'son' => collect(json_decode(self::getCategoryAllItems()))
              ->filter(function($son) use ($parent){
                return $son->parent_id == $parent->id && $son->status;
              })
              ->map(function($son){
                return [
                  'cid' => $son->id,
                  'pid' => $son->parent_id,
                  'name' => $son->name,
                  'sort' => $son->sort,
                  'img' => $son->img,
                  'url' => $son->url,
                  'end' => collect(json_decode(self::getCategoryAllItems()))
                    ->filter(function($end) use ($son){
                      return $end->parent_id == $son->id && $end->status;
                    })
                    ->map(function($end){
                      return [
                        'cid' => $end->id,
                        'pid' => $end->parent_id,
                        'name' => $end->name,
                        'sort' => $end->sort,
                        'img' => $end->img,
                        'url' => $end->url,
                      ];
                    })
                ];
              })
          ];
        });

      $push = collect([
        'cid' => -1,
        'pid' => -1,
        'name' => '热门推荐',
        'sort' => -1,
        'img' => '',
        'url' => '',
        'son' => collect()->push(
        // 推荐子类目，后面还可以添加其他自定义子类目，如热卖品牌等
          [
            'cid' => -1,
            'pid' => -1,
            'name' => '热门推荐',
            'sort' => -1,
            'img' => '',
            'url' => '',
            'end' => collect(json_decode(self::getCategoryAllItems()))
              ->filter(function($item){
                return $item->show_in_nav && $item->status; // 过滤推荐并启用的类目
              })
              ->map(function($end){
                return [
                  'cid' => $end->id,
                  'pid' => $end->parent_id,
                  'name' => $end->name,
                  'sort' => $end->sort,
                  'img' => $end->img,
                  'url' => $end->url,
                ];
              })
          ]
        )
      ]);

      $tree->push($push)->toJson();

      //ddd($tree);
      $time = CATEGORY_TREE_OF_MOBILE_CACHE_TIME;
      app('cache')->put($key, $tree, $time);
    }

    return app('cache')->get($key);
  }

  /**
   * 使用 类目id 获取：该类目下的所有末端类目ids（含子类目）
   *
   * @param int $cid 类目id
   *
   * @return string JSON
   *
   * @author AlpFish 2016/8/13 12:50
   */
  public static function getEndCategoryIdsWithCategoryid($cid)
  {
    $key = END_CATEGORY_IDS_WITH_CATEGORY_ID_CACHE . $cid;
    if (! app('cache')->has($key)) {

      // 集合处理缓存数据 （较数组处理方法性能丢失不大可以忽略，但集合拥有更便捷直观的管道方法，所以推荐缓存使用JSON, 操作使用集合）
      $all = collect(json_decode(self::getCategoryAllItems()))->transform(function($item){
        return $item = ['id' => $item->id, 'parent_id' => $item->parent_id];
      });

      $pids = $all->pluck('parent_id')->unique();
      if (! $pids->contains($cid)) {
        $ends = json_encode([(int)$cid]);
      } elseif ($cid == 0){
        $ends = $all->pluck('id')->diff($pids)->flatten()->toJson();
      } else{
        $ends = $all->whereLoose('parent_id', $cid)->pluck('id')->toJson();
      }
      $time = END_CATEGORY_IDS_WITH_CATEGORY_ID_CACHE_TIME;
      app('cache')->put($key, $ends, $time);
    }

    return app('cache')->get($key);
  }

  /**
   * 使用 端点类目id 获取: 该端点类目下所有 商品id 和 search_score
   *
   * @param int $cid
   *
   * @return string JSON
   *
   * @author AlpFish 2016/8/13 19:55
   */
  public static function getGoodsIdsAndSortWithEndCid($cid)
  {
    $key = GOODS_IDS_AND_SORT_WITH_END_CID_CACHE . $cid;
    if (! app('cache')->has($key)) {
      $result = null;
      if ($category = Category::find($cid)) {
        $goods = $category->goods()->get();
        // search_score 是 Goods 模型的一个访问器属性，不能通过查询构建器 lists() 获取其值，可以通过集合获取
        $result = $goods->map(function($item){
          return array (
            'id' => $item->id,
            'sort' => $item->sort
          );
        });
      };
      $result = json_encode($result);

      $time = GOODS_IDS_AND_SORT_WITH_END_CID_CACHE_TIME;
      app('cache')->put($key, $result, $time);
    }

    return app('cache')->get($key);
  }


  // 类目的商品ids
  public static function getGoodsIdsByCid($cid)
  {
    $key  = sprintf(CATEGORY_ID_GOODS_IDS, $cid);
    $time = CATEGORY_ID_GOODS_IDS_TIME;

    if (! app('cache')->has($key)) {
      $goods_ids = [];
      $end_cids  = json_decode(self::getEndCidsByCid($cid));
      foreach ($end_cids as $end_cid){
        $ids = Category::find($end_cid)->goods()->lists('id');
        $goods_ids = array_merge($goods_ids, (array)$ids->all());
      }
      $goods_ids = json_encode(array_unique($goods_ids));

      app('cache')->put($key, $goods_ids, $time);
    }

    return app('cache')->get($key);
  }

  // 类目的叶类目
  public static function getEndCidsByCid($cid)
  {
    $key = sprintf(CATEGORY_ID_END_IDS, $cid);
    if (! app('cache')->has($key)) {

      $all = collect(json_decode(self::getCategoryAllItems()))->transform(function($item){
        return $item = ['id' => $item->id, 'parent_id' => $item->parent_id];
      });

      $pids = $all->pluck('parent_id')->unique();
      if (! $pids->contains($cid)) {
        $ends = json_encode([$cid]);
      } elseif ($cid == 0){
        $ends = $all->pluck('id')->diff($pids)->flatten()->toJson();
      } else{
        $ends = $all->whereLoose('parent_id', $cid)->pluck('id')->toJson();
      }
      $time = CATEGORY_ID_END_IDS_TIME;
      app('cache')->put($key, $ends, $time);
    }

    return app('cache')->get($key);
  }

}