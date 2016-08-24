<?php

namespace App\Repositories\Caches\Goods\Category;

use App\Repositories\Caches\RedisKeys as Keys;
use App\Models\Goods\Category\Category;

class CategoryCacheRepository
{
    /**
     * 获取元数据 （HASH结构）
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
    public static function getCell($id, $fields)
    {
        $key = sprintf(Keys::CATEGORY_CELLS_ID[ 'key' ], $id);

        if (! app('redis')->exists($key)) {
            if ($item = Category::find($id)) {
                $fs = array_keys($item->toArray());
                app('redis')->pipeline(function($pipe) use ($key, $fs, $item){
                    foreach ($fs as $f){
                        $pipe->hset($key, $f, $item->$f);
                    }
                });
                $time = Keys::CATEGORY_CELLS_ID[ 'time' ];
                app('redis')->expire($key, $time);
            }
        }
        $data = is_array($fields)
            ? array_combine($fields, app('redis')->hmget($key, $fields))
            : app('redis')->hget($key, $fields);

        return $data;
    }

    /**
     * 类目 id 集合 (SET)
     *
     * @return array
     *
     * @author AlpFish 2016/8/22 17:10
     */
    public static function getIds()
    {
        $key = Keys::CATEGORY_SETS_IDS[ 'key' ];
        if (! app('redis')->exists($key)) {
            $ids = Category::lists('id')->all();
            // 管道有效
            app('redis')->pipeline(function($pipe) use ($ids, $key){
                foreach ($ids as $id){
                    $pipe->sadd($key, $id);
                }
            });
            $time = Keys::CATEGORY_SETS_IDS[ 'time' ];
            app('redis')->expire($key, $time);

            return $ids;
        }

        return app('redis')->smembers($key);
    }

    /**
     * 类目 parent_id 集合 (SET)
     *
     * @return array
     *
     * @author AlpFish 2016/8/22 23:10
     */
    public static function getPids()
    {
        $key = Keys::CATEGORY_SETS_PIDS[ 'key' ];
        if (! app('redis')->exists($key)) {

            $pids  = Category::lists('parent_id')->unique()->all();
            $pipes = app('redis')->pipeline(function($pipe) use ($key, $pids){
                foreach ($pids as $pid){
                    $pipe->sadd($key, $pid);
                }
            });

            $time = Keys::CATEGORY_SETS_PIDS[ 'time' ];
            app('redis')->expire($key, $time);
        }

        return app('redis')->smembers($key);
    }

    /**
     * 类目 id 下的商品ids (包括子类目)
     *
     * @param int $cid
     *
     * @return array
     *
     * @author AlpFish 2016/8/23 10:40
     */
    public static function getGoodsIdsByCid($cid)
    {
        $cells = sprintf(Keys::CATEGORY_CELLS_ID[ 'key' ], $cid);
        $field = 'goods_ids';

        if (! app('redis')->hexists($cells, $field)) {
            $goods_ids = [];
            $end_cids  = self::getEndCidsByCid($cid);
            foreach ($end_cids as $end_cid){
                $ids       = Category::find($end_cid)->goods()->lists('id');
                $goods_ids = array_merge($goods_ids, (array)$ids->all());
            }
            $goods_ids = json_encode(array_unique($goods_ids));

            app('redis')->hset($cells, $field, $goods_ids);
        }

        return json_decode(app('redis')->hget($cells, $field));
    }

    /**
     * 类目 id 下的叶类目
     *
     * @param int $cid
     *
     * @return array
     *
     * @author AlpFish 2016/8/22 23:40
     */
    public static function getEndCidsByCid($cid)
    {
        $cell  = sprintf(Keys::CATEGORY_CELLS_ID[ 'key' ], $cid);
        $field = 'end_ids';
        if (! app('redis')->hexists($cell, $field)) {

            $cids = self::getIds();
            $pids = self::getPids();
            if (! in_array($cid, $pids)) {
                $ends = [$cid];
            } elseif ($cid == 0){
                $ends = array_values(array_diff($cids, $pids));
            } else{
                $ends = [];
                foreach ($cids as $id){
                    if ($cid == self::getCell($id, 'parent_id')) {
                        $ends[] = $id;
                    }
                };
            }
            $ends = json_encode($ends);
            app('redis')->hset($cell, $field, $ends);
        }

        return json_decode(app('redis')->hget($cell, $field));
    }

    /**
     * 获取类目树型结构
     *
     * @return strin JSON
     *
     * @author AlpFish 2016/8/19 7:33
     */
    public static function getTreeOfMobile()
    {
        $key = Keys::CATEGORY_STRS_TREE[ 'key' ];
        if (! $tree = app('redis')->get($key)) {
            $all  = Category::select('id', 'parent_id', 'name', 'sort', 'img', 'url', 'status', 'show_in_nav')->get();
            $tree = $all->filter(function($parent){
                return $parent->parent_id == 0 && $parent->status;
            })->map(function($parent) use ($all){
                return [
                    'cid' => $parent->id,
                    'pid' => $parent->parent_id,
                    'name' => $parent->name,
                    'sort' => $parent->sort,
                    'img' => $parent->img,
                    'url' => $parent->url,
                    'son' => $all->filter(function($son) use ($parent){
                        return $son->parent_id == $parent->id && $son->status;
                    })->map(function($son) use ($all){
                        return [
                            'cid' => $son->id,
                            'pid' => $son->parent_id,
                            'name' => $son->name,
                            'sort' => $son->sort,
                            'img' => $son->img,
                            'url' => $son->url,
                            'end' => $all->filter(function($end) use ($son){
                                return $end->parent_id == $son->id && $end->status;
                            })->map(function($end){
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
                        'end' => $all->filter(function($item){
                            return $item->show_in_nav && $item->status; // 过滤推荐并启用的类目
                        })->map(function($end){
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
            $tree = $tree->push($push)->toJson();

            $time = Keys::CATEGORY_STRS_TREE['time'];
            app('redis')->set($key, $tree, 'ex', $time);
        }

        return json_decode($tree);
    }

}