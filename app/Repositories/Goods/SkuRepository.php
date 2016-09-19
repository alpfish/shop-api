<?php

namespace App\Repositories\Goods;

use App\Models\Goods\Sku\Sku;
use App\Repositories\Caches\Goods\SkuCacheRepository as SkuCache;

class SkuRepository
{

    /**
     * 按主键获取SKU
     *
     * @param array|string $ids    sku_ids
     * @param array|string $fields 获取哪些字段的值
     * @param boolean      $cache  是否使用缓存
     *
     * @return Array|null
     *
     * Author AlpFish 2016/9/17
     */
    static public function find($ids, $fields = [ ], $cache = true)
    {
        $ids = is_array($ids) ? $ids : [ $ids ];
        if ($cache && count($ids) <= 50) { # 缓存获取
            $data = [ ];
            foreach ($ids as $id){
                $data[] = SkuCache::cell($id, $fields);
            }

            return $data;
        } else{ # 数据库获取
            if (empty( $fields )) {
                return Sku::whereIn('sku_id', $ids)->get();
            }

            return Sku::select((array)$fields)->whereIn('sku_id', $ids)->get()->toArray();
        }
    }
}