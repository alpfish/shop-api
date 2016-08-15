<?php


namespace App\Api\V1\Goods;


use App\Repositories\Goods\Category\CategoryRepository;

class SearchGoodsController
{
    /****************************************************************************************
     *
     * 商品搜索 API
     *
     * @by              Alpfish 2016/8/14 6:35
     * @api             {GET} goods/search 商品搜索
     * @apiName         商品搜索
     * @apiGroup        Goods
     * @apiVersion      1.0.0
     * @apiPermission   public
     * @apiDescription  可根据 cid, ids, keywords, tags... 等选项搜索商品
     *
     * @apiParam {string}   [type=list] 搜索类型，支持 list（列表）, detail（详情）, sku
     * @apiParam {int}      [cid]       根据类目id 搜索商品，支持1|2|3级类目, 0 表示所有类目，搜索结果包括子类目商品。
     * @apiParam {int[]}    [ids]       根据商品ids 数组搜索商品。
     * @apiParam {string}   [keywords]  根据关键词搜索商品。
     * @apiParam {string}   [tags]      根据标签搜索商品，如类目推荐，猜你喜欢。
     * @apiParam {int}      [page=1]    搜索结果页码序号。
     * @apiParam {int}      [limit=240] 搜索结果返回记录数量，因为是API调用，limit 应为前端每页显示条目的倍数。
     *
     * @apiSuccess  (成功返回) {array}      searched_goods 搜索到的商品数组，包括调用成功返回的其他字段：
     * @apiSuccess  (成功返回) {int}        id             商品id
     * @apiSuccess  (成功返回) {string}     name           商品名称
     * @apiSuccess  (成功返回) {string}     thumb          商品缩略图url（不含域名，前端拼接）
     * @apiSuccess  (成功返回) {number}     orig           原价（original price）
     * @apiSuccess  (成功返回) {number}     price          价格
     * @apiSuccess  (成功返回) {int}        sales          销量
     * @apiSuccess  (成功返回) {int}        quantity       库存数量，单 sku 为此 sku 库存，多 sku 为所有 sku 库存之和。
     * @apiSuccess  (成功返回) {number}     search_score   搜索得分（默认综合排序使用）
     * @apiSuccess  (成功返回) {boolean}    only_sku       单 sku 商品（为 true 时表示可直接加入购物车）
     * @apiSuccess  (成功返回) {int}        [only_sku_id]  单 sku 时的 sku_id，方便在列表页中直接加入购物车，多 sku 时异步请求 sku 数据
     * @apiSuccess  (成功返回) {string}     [tag_thumb]    缩略图标签，唯一
     * @apiSuccess  (成功返回) {string}     [tag_prom]     促销标签，唯一，包括商品销售和限时促销
     * @apiSuccess  (成功返回) {string}     [features]     商品特征, 多个特征用' , '分隔
     *
     * @apiError    (响应状态) {int}        status_code    响应状态码，默认200：调用成功；4XX：请求错误；5XX：服务器错误
     * @apiError    (响应状态) {string}     message        响应状态信息, 默认 "Success"
     */
    public function index()
    {
        //$query = app('request')->all();
        $query = app('request')->get('cid');

        $s = timer();
        if (isset($query)) {
            $data = CategoryRepository::getGoodsOfSearchListWithCategoryId($query);
            return ['searched' => $data];
        }

        ddd(timer() - $s);
    }

    protected function getCategoryGoodsSearch($cid = 0, $page = 1, $limit = 240)
    {
        $data = CategoryRepository::getGoodsSearchListFromCategoryId($cid, $page, $limit);
        return ['searched' => $data];
    }
}