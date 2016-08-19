<?php
/**
 * 缓存键常量定义
 *
 * 只能用于数据缓存仓库
 */

/**
 * GoodsCacheRopository
 */
// 使用 商品id 缓存: 商品条目原值
define('GOODS_ITEM_OF_ALL_FIELDS_WITH_GOODS_ID_CACHE', 'goods_item_of_all_fields_with_goods_id_');
define('GOODS_ITEM_OF_ALL_FIELDS_WITH_GOODS_ID_CACHE_TIME', 30);

// 使用 商品id 缓存: 搜索列表下的商品条目（搜索列表指定字段）
define('GOODS_ITEM_OF_SEARCH_LIST_WITH_GOODS_ID_CACHE', 'goods_item_of_search_list_with_goods_id_');
define('GOODS_ITEM_OF_SEARCH_LIST_WITH_GOODS_ID_CACHE_TIME', 30);

/**
 * CategoryCacheRopository
 */
// 类目所有条目
define('CATEGORY_ALL_ITEMS_CACHE', 'category_all_items');
define('CATEGORY_ALL_ITEMS_CACHE_TIME', 30);

// 类目树
define('CATEGORY_TREE_OF_MOBILE_CACHE', 'category_tree_of_mobile');
define('CATEGORY_TREE_OF_MOBILE_CACHE_TIME', 30);

// 使用 类目ID 缓存：该类目下的所有端点（叶）类目ids
define('END_CATEGORY_IDS_WITH_CATEGORY_ID_CACHE', 'end_category_ids_with_category_id_');
define('END_CATEGORY_IDS_WITH_CATEGORY_ID_CACHE_TIME', 30);

// 使用 端点类目id 获取: 该端点类目下所有 商品id 和 search_score
define('GOODS_IDS_AND_SORT_WITH_END_CID_CACHE', 'goods_ids_and_sort_with_end_cid_');
define('GOODS_IDS_AND_SORT_WITH_END_CID_CACHE_TIME', 30);
