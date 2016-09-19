<?php
namespace App\Repositories\Caches\Goods;

use App\Models\Goods\Sku\Sku;
use App\Repositories\Caches\RedisKeys as Keys;

class SkuCacheRepository
{
    /**
     * 获取元数据 （HASH结构）
     *
     * 可以获取数据库表原型字段和模型中使用get()方法自定义的字段数据，
     * 但使用缓存仓库中的计算方法得出的字段数据不要通过该方法获取，必须用相关方法获取，
     * 因为获取时有可能还未添加该字段到缓存中，会返回空。
     *
     * @param int          $id     主键
     * @param string|array $fields 字段名, 默认获取全部字段
     *
     * @return string|array 一个字段则直接返回字段值，多个值返回字段名 => 字段值的数组格式数据。
     *
     * @author AlpFish 2016/8/22 21:10
     */
    public static function cell($id, $fields = [ ])
    {
        $key = sprintf(Keys::SKU_CELLS_ID[ 'key' ], $id);

        if (!app('redis')->exists($key)) {
            if ($item = Sku::find($id)) {
                app('redis')->pipeline(function($pipe) use ($key, $item){
                    $fields = array_keys($item->toArray());
                    foreach ($fields as $f){
                        $pipe->HSET($key, $f, $item->$f);
                    }
                });
                $time = Keys::SKU_CELLS_ID[ 'time' ];
                app('redis')->expire($key, $time);
            }
        }
        if (empty( $fields )) {
            $data = app('redis')->HGETALL($key);
        } else{
            $data = is_array($fields)
                ? array_combine($fields, app('redis')->HMGET($key, $fields))
                : app('redis')->HGET($key, $fields);
        }

        return $data;
    }
}