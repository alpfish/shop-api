<?php
/**
 * 缓存键常量定义
 *
 * 只能用于数据缓存仓库
 */

// 使用 商品id 缓存: 搜索列表下的商品条目（搜索列表指定字段）
define('GOODS_ITEM_OF_SEARCH_LIST_WITH_GOODS_ID_CACHE', 'goods_item_of_search_list_with_goods_id_');
define('GOODS_ITEM_OF_SEARCH_LIST_WITH_GOODS_ID_CACHE_TIME', 30);

// 使用 商品id 缓存: 商品条目所有数据
define('GOODS_ITEM_ALL_DATA_WITH_GOODS_ID_CACHE', 'goods_item_all_data_with_goods_id_');
define('GOODS_ITEM_ALL_DATA_WITH_GOODS_ID_CACHE_TIME', 30);

// 使用 类目id 缓存：其下所有商品ids 与 search_score
define('GOODS_IDS_AND_SEARCH_SCORE_WITH_CATEGORY_ID_CACHE', 'goods_ids_and_search_score_with_category_id_');
define('GOODS_IDS_AND_SEARCH_SCORE_WITH_CATEGORY_ID_CACHE_TIME', 30);

// 类目所有条目
define('CATEGORY_ALL_ITEMS_CACHE', 'category_all_items');
define('CATEGORY_ALL_ITEMS_CACHE_TIME', 30);

// 使用 类目ID 缓存：其下的所有端点（叶）类目ids
define('CATEGORY_END_IDS_WITH_CATEGORY_ID_CACHE', 'category_end_ids_with_category_id_');
define('CATEGORY_END_IDS_WITH_CATEGORY_ID_CACHE_TIME', 30);