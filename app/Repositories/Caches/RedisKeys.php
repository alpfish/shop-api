<?php
namespace App\Repositories\Caches;

/*
 * 格式及含义：
 *
 * [常量] MODEL_CELLS_PK
 * [键名] model:cells:pk
 * [结构] HASH
 * [数据] 模型下字段和字段值的列表数据（包自定义或计算字段）
 * [说明] PK 即主键，该类缓存是模型数据以主键为索引在内存中的复制和变形。
 *
 * [常量] MODEL_SETS_NAME
 * [键名] model:sets:name
 * [结构] SET
 * [数据] 模型下一类属性值的集合
 * [说明] 此类缓存的作用是从数据库或缓存中提取大并发数据以提升性能。
 *
 * [常量] MODEL_SORTS_FEILD
 * [键名] model:sorts:feild
 * [结构] Sorted SET
 * [数据] 排序字段值的集合
 * [说明]
 *
 * [常量] MODEL_STRS_KEYNAME
 * [键名] model:strs:key.name
 * [结构] String
 * [数据] 模型下字符串或JSON字符串键值对数据。
 * [说明] 除以上类型的其他数据。
 *
 * */
class RedisKeys
{
    const MEMBER_MODEL_ID = [ 'key' => 'member:model:%s', 'time' => 60 * 60 ];

    const GOODS_CELLS_ID     = [ 'key' => 'goods:cells:%s', 'time' => 60 * 30 ];
    const GOODS_SORTED_PRICE = [ 'key' => 'goods:sorted:price', 'time' => 60 * 30 ];
    const GOODS_SORTED_SALES = [ 'key' => 'goods:sorted:sales', 'time' => 60 * 60 * 8 ];
    const GOODS_SORTED_SORT  = [ 'key' => 'goods:sorted:sort', 'time' => 60 * 60 * 12 ];

    const SKU_CELLS_ID = [ 'key' => 'sku:cells:%s', 'time' => 60 * 10 ];

    const CATEGORY_CELLS_ID  = [ 'key' => 'category:cells:%s', 'time' => 60 * 60 * 24 ];
    const CATEGORY_SETS_IDS  = [ 'key' => 'category:sets:ids', 'time' => 60 * 60 * 24 ];
    const CATEGORY_SETS_PIDS = [ 'key' => 'category:sets:pids', 'time' => 60 * 60 * 24 ];
    const CATEGORY_STRS_TREE = [ 'key' => 'category:strs:tree', 'time' => 60 * 60 * 24 ];

    const PROMOTION_ORDER_STRS_ALL = [ 'key' => 'promotion:order:strs:all', 'time' => 60 * 10 ];
    const PROMOTION_GOODS_STRS_ID = [ 'key' => 'promotion:goods:strs:%s', 'time' => 60 * 10 ];
    const PROMOTION_TIME_STRS_ID = [ 'key' => 'promotion:time:strs:%s', 'time' => 60 * 10 ];

}