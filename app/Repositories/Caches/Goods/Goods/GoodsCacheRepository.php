<?php


namespace App\Repositories\Caches\Goods\Goods;

use App\Models\Goods\Goods\Goods;

class GoodsCacheRepository
{

    /**
     * 用 商品id 获取缓存中的: 所有字段下的商品条目
     *
     * 数据来源于 Goods 模型，缓存中其他商品条目数据均来源于此。
     *
     * @param int $id spu_id
     *
     * @return string JSON
     *
     * @author AlpFish 2016/8/14 8:20
     */
    public static function getGoodsItemOfAllFieldsWithGoodsId($id)
    {
        $key = GOODS_ITEM_OF_ALL_FIELDS_WITH_GOODS_ID_CACHE . $id;

        if (! app('cache')->has($key)) {
            $data = null;
            if ($item = Goods::find($id)) {
                // 使用 toJson() 获取模型实例会自动添加 $appends 中追加的访问器属性, 删除 $hidden 中的隐藏字段
                $data = $item->toJson();
            }

            $time = GOODS_ITEM_OF_ALL_FIELDS_WITH_GOODS_ID_CACHE_TIME;
            app('cache')->put($key, $data, $time);
        }

        return app('cache')->get($key, null);
    }

    /**
     * 用 商品id 获取缓存中: 搜索列表项下的商品条目
     *
     * @param int $id spu_id
     *
     * @return string JSON
     *
     * @author AlpFish 2016/8/13 9:29
     */
    public static function getGoodsItemOfSearchListWithGoodsId($id)
    {
        $key = GOODS_ITEM_OF_SEARCH_LIST_WITH_GOODS_ID_CACHE . $id;

        if (! app('cache')->has($key)) {
            $item   = collect(json_decode(self::getGoodsItemOfAllFieldsWithGoodsId($id)));
            $filter = null;
            if (! $item->isEmpty() && $item->get('is_sale')) {
                $filter = $item->only(
                    'id',
                    'name',             // 商品名
                    'thumb',            // 缩略图
                    'orig',             // 原价
                    'price',            // 销售价
                    'quantity',         // 数量
                    'sales',            // 销量
                    'sort',             // 排序
                    'only_sku',         // 单一SKU
                    'only_sku_id',      // 单一SKU id
                    'tag_thumb',        // 缩略图标签
                    'tag_prom',         // 优惠促销标签
                    'features'          // 特征卖点标签

                );
            }
            $filter = json_encode($filter);

            $time = GOODS_ITEM_OF_SEARCH_LIST_WITH_GOODS_ID_CACHE_TIME;
            app('cache')->put($key, $filter, $time);
        }

        return app('cache')->get($key);
    }

    /**
     * 获取商品元数据
     *
     * @param int    $id    主键
     * @param string $field 字段名
     *
     * @return mixed
     *
     * @author AlpFish 2016/8/21 23:10
     */
    public static function getGoodsCell($id, $field)
    {
        $key  = sprintf('goods:%s:%s', $id, $field);
        $time = 30 * 60;
        if (! app('cache')->has($key)) {
            if ($item = Goods::find($id)) {
                $fields = array_keys($item->toArray());
                foreach ($fields as $f){
                    $k = sprintf('goods:%s:%s', $id, $f);
                    app('cache')->put($k, $item->$f, $time);
                }
            } else{
                app('cache')->put($key, null, $time);
            }
        }

        return app('cache')->get($key, null);
    }

}