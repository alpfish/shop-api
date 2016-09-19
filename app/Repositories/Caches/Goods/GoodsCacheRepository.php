<?php


namespace App\Repositories\Caches\Goods;

use App\Models\Goods\Goods\Goods;
use App\Repositories\Caches\RedisKeys as Keys;

class GoodsCacheRepository
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
    public static function cell($id, $fields)
    {
        $key = sprintf(Keys::GOODS_CELLS_ID[ 'key' ], $id);

        if (! app('redis')->exists($key)) {
            if ($item = Goods::find($id)) {
                $_fields = array_keys($item->toArray());
                app('redis')->pipeline(function($pipe) use ($key, $_fields, $item){
                    foreach ($_fields as $f){
                        $pipe->hset($key, $f, $item->$f);
                    }
                });
                $time = Keys::GOODS_CELLS_ID[ 'time' ];
                app('redis')->expire($key, $time);
            }
        }
        $data = is_array($fields)
            ? array_combine($fields, app('redis')->hmget($key, $fields))
            : app('redis')->hget($key, $fields);

        return $data;
    }

    /**
     * 排序所给商品ids
     *
     * @param array  $ids
     * @param string $field = sort 排序字段: sales, price, sort
     * @param string $order = asc  排序顺序: sales, price, sort
     *
     * @return array $ids
     *
     * @author AlpFish 2016/8/23 20:04
     */
    public static function sortByIds($ids = array (), $field = 'sort', $order = 'asc')
    {
        $order = strtolower($order) == 'asc' ? 'asc' : 'dec';
        switch (strtolower($field)){
            case 'sales':
                $key   = Keys::GOODS_SORTED_SALES[ 'key' ];
                $field = 'sales';
                if (! app('redis')->exists($key)) {
                    $time = Keys::GOODS_SORTED_SALES[ 'time' ];
                    app('redis')->zadd($key, 0, 0);
                    app('redis')->expire($key, $time);
                }
                break;
            case 'price':
                $key   = Keys::GOODS_SORTED_PRICE[ 'key' ];
                $field = 'price';
                if (! app('redis')->exists($key)) {
                    $time = Keys::GOODS_SORTED_PRICE[ 'time' ];
                    app('redis')->zadd($key, 0, 0);
                    app('redis')->expire($key, $time);
                }
                break;
            default:
                $key   = Keys::GOODS_SORTED_SORT[ 'key' ];
                $field = 'sort';
                if (! app('redis')->exists($key)) {
                    $time = Keys::GOODS_SORTED_SORT[ 'time' ];
                    app('redis')->zadd($key, 0, 0);
                    app('redis')->expire($key, $time);
                }
        }

        $z   = $order == 'asc' ? app('redis')->zrange($key, 0, -1, 'WITHSCORES') : app('redis')->zrevrange($key, 0, -1, 'WITHSCORES');
        $add = array_diff($ids, array_keys($z));  //值比较
        if ($add) {
            $pipes = app('redis')->pipeline(function($pipe) use ($add, $key, $field){
                foreach ($add as $id){
                    $score = self::cell($id, $field);
                    $pipe->zadd($key, $score, $id);
                }
            });

            $z = $order == 'asc' ? app('redis')->zrange($key, 0, -1, 'WITHSCORES') : app('redis')->zrevrange($key, 0, -1, 'WITHSCORES');
        }
        $sorted_ids = array_intersect_key($z, array_flip($ids)); // 键比较求交集，需反转$ids

        //ddd($z, $ids, $add, $sorted);
        return array_keys($sorted_ids);
    }
}