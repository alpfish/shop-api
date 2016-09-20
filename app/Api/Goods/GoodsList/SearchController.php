<?php
namespace Api\Goods\GoodsList;

/*******************************************************************************************
 *
 * 商品搜索
 *
 * 是指对 <商品列表> 数据的搜索，商品详情，SKU，评论等其他类型不包括在此。
 *
 * 搜索算法：
 *
 * 1. 筛选因子：cid, brand_id, keywords, tag(标签)，spec, attr, only_prom(促销) category:1:goods:ids
 *    筛选因子可根据业务需要进行扩展
 *    将各个筛选出的商品ids作交集，得到搜索的ids结果集
 *    [性能]：将筛选因子作缓存键，筛选出的商品ids作缓存值
 *
 * 2. 排序因子：sort: default, sales, price
 *    后端能对整个结果集进行排序，前端只能对部分结果集排序，故在后端排序是必须的。
 *    [性能]直接从缓存getGoodsCell()获取排序键值
 *
 * 3. 分页因子: page, per_page
 *    将筛选并排序后的商品IDS进行分页
 *
 * 4. 获取商品列表数据
 *    按返回客户端的ids集从商品缓存中获取商品列表数据
 *    [性能]直接从缓存cell()获取商品数据
 *
 * 性能提升：
 *
 * 1. 以上缓存键主要作用是分化搜索数据到内存，降低数据库压力
 * 2. 搜索参数为键，搜索结果为值对每个搜索进行缓存，可以大大降低服务器的处理压力，
 *    每次搜索请求从缓存中取出结果直接响应到客服端即可。
 *    备注：因为第一点已经大大提升了性能，大多请求都在1s内完成。故服务器压力不大时无需缓存搜索结果。
 *
 * */
use App\Repositories\Goods\CategoryRepository;
use App\Repositories\Goods\GoodsRepository as Goods;

class SearchController
{
    protected $cid      = null;
    protected $brand_id = null;
    protected $keywords = null;
    protected $sort     = 'sort';
    protected $page     = 1;
    protected $per_page = 100;

    public function __construct()
    {
        $this->setSearchParams();

        if (is_null($this->cid) && is_null($this->keywords) && is_null($this->brand_id)) {
            throw new \Exception('必须包含以下其中一个参数：cid, keywords, brand_id', 400);
        }
    }

    /****************************************************************************************
     * 商品搜索 API
     * Alpfish 2016/8/14 6:35
     *
     * @api             {GET} goods/search search
     * @apiName         商品搜索
     * @apiGroup        Goods
     * @apiVersion      1.0.0
     * @apiPermission   public
     * @apiDescription  商品搜索, 搜索请求参数必须含有以下其中一个：cid, keywords, ids, tag。
     *                  其中，cid, page, per_page, total, goods 已被固定使用，勿修改变量名。
     *
     * @apiParam {int}      [cid=null]          根据类目id 搜索商品，支持1|2|3级类目, 0 表示所有类目，搜索结果包括子类目商品。
     * @apiParam {string}   [keywords=null]     根据关键词搜索商品。
     * @apiParam {string}   [brand_id=null]     品牌筛选键
     * @apiParam {string}   [sort=null]         结果排序，默认综合排序，值为'sd'按销量降序，'pa'价格升序，'pd'价格降序
     * @apiParam {int}      [page=1]            搜索结果页码序号。
     * @apiParam {int}      [per_page=120]      搜索结果返回记录数量，因为是API调用，limit 应为前端每页显示条目的倍数。
     *
     * @apiSuccess  (响应成功) {int}        total                商品总数
     * @apiSuccess  (响应成功) {int}        page                 当前页数
     * @apiSuccess  (响应成功) {int}        per_page             每页记录数量
     * @apiSuccess  (响应成功) {array}      goods                商品数据容器，包括子元素：id, name, thumb, orig，...
     * @apiSuccess  (响应成功) {int}        goods:id             商品id
     * @apiSuccess  (响应成功) {string}     goods:name           商品名称
     * @apiSuccess  (响应成功) {string}     goods:thumb          商品缩略图url（不含域名，前端拼接）
     * @apiSuccess  (响应成功) {number}     goods:orig           原价（original price）
     * @apiSuccess  (响应成功) {number}     goods:price          价格
     * @apiSuccess  (响应成功) {int}        goods:sales          销量
     * @apiSuccess  (响应成功) {int}        goods:quantity       库存数量，单 sku 为此 sku 库存，多 sku 为所有 sku 库存之和。
     * @apiSuccess  (响应成功) {number}     goods:sort           商品顺序：按后台搜索质量得分降序排列（默认综合排序使用）
     * @apiSuccess  (响应成功) {boolean}    goods:only_sku       单 sku 商品（为 true 时表示可直接加入购物车）
     * @apiSuccess  (响应成功) {int}        goods:only_sku_id    单 sku 时的 sku_id，方便在列表页中直接加入购物车，多 sku 时异步请求 sku 数据
     * @apiSuccess  (响应成功) {string}     goods:tag_thumb      缩略图标签，唯一
     * @apiSuccess  (响应成功) {string}     goods:tag_prom       促销标签，唯一，包括商品销售和限时促销
     * @apiSuccess  (响应成功) {string}     goods:features       商品特征, 多个特征用' , '分隔
     *
     * @apiError    (响应错误) {int}        status_code    响应失败状态码，（默认200为响应成功）；4XX：请求错误；5XX：服务器错误
     * @apiError    (响应错误) {string}     message        响应失败状态信息
     * @apiError    (响应错误) {array}      errors         响应失败错误信息
     */
    public function index()
    {
        // searched  //此处可缓存
        $ids = $this->getGoodsIds();

        // sort and page
        $filteredIds = $this->filterGoodsIds($ids);

        // get goods
        // $goods = $this->getGoodsByIds($filteredIds);

        // data
        return [
            'total' => count($ids),
            'page' => $this->page,
            'per_page' => $this->per_page,
            'goods' => $this->getGoodsByIds($filteredIds),
        ];
    }

    protected function setSearchParams()
    {
        $r = app('request');

        $this->cid      = $r->has('cid') ? (int)$r->cid : $this->cid;
        $this->brand_id = $r->has('brand_id') ? (int)$r->brand_id : $this->brand_id;
        $this->keywords = $r->has('keywords') ? trim($r->keywords) : $this->keywords;
        $this->sort     = $r->has('sort') && in_array(mb_strtolower($r->sort), ['sd', 'pa', 'pd']) ? mb_strtolower($r->sort) : $this->sort;
        $this->page     = $r->has('page') ? (int)$r->page : $this->page;
        $this->per_page = $r->has('per_page') ? (int)$r->per_page : $this->per_page;
    }

    protected function getGoodsIds()
    {
        if ($this->cid !== null) {
            return CategoryRepository::getGoodsIdsByCid($this->cid);
        }

        return null;
    }

    protected function filterGoodsIds($ids)
    {
        $direction = 'asc';
        if ($this->sort === 'sd') {
            $this->sort = 'sales';
        } elseif ($this->sort === 'pa'){
            $this->sort = 'price';
        } elseif ($this->sort === 'pd'){
            $this->sort = 'price';
            $direction  = 'dec';
        }

        $ids = Goods::sortByIds($ids, $this->sort, $direction);

        return collect($ids)->forPage($this->page, $this->per_page)->all();
    }

    protected function getGoodsByIds($ids)
    {
        $goods = [];
        foreach ($ids as $id){
            $goods[] = Goods::cell(
                $id,
                [
                    'id',
                    'name',         // 商品名
                    'thumb',        // 缩略图
                    'orig',         // 原价
                    'price',        // 销售价
                    'quantity',     // 数量
                    'sales',        // 销量
                    'sort',         // 排序
                    'only_sku',     // 单一SKU
                    'only_sku_id',  // 单一SKU id
                    'tag_thumb',    // 缩略图标签
                    'tag_prom',     // 优惠促销标签
                    'features',     // 特征卖点标签
                ]
            );
        }

        return $goods;
    }

}

