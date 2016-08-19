<?php


namespace App\Api\V1\Goods;


use App\Repositories\Goods\Category\CategoryRepository;

class SearchGoodsController
{
  /****************************************************************************************
   * 商品搜索 API
   *
   * @by              Alpfish 2016/8/14 6:35
   * @api             {GET} goods/search 商品搜索
   * @apiName         商品搜索
   * @apiGroup        Goods
   * @apiVersion      1.0.0
   * @apiPermission   public
   * @apiDescription  商品搜索, 搜索请求参数必须含有以下其中一个：cid, keywords, ids, tag
   *
   * @apiParam {string}   [origin=mobile]     搜索来源，包括 mobile, pc
   * @apiParam {string}   [type=list]         搜索类型，支持 list（列表）, detail（详情）, sku
   * @apiParam {string}   [keywords=null]     根据关键词搜索商品。
   * @apiParam {int}      [cid=null]          根据类目id 搜索商品，支持1|2|3级类目, 0 表示所有类目，搜索结果包括子类目商品。
   * @apiParam {int[]}    [ids=null]          根据商品ids 数组搜索商品。
   * @apiParam {string}   [tag=null]          根据标签搜索商品，如类目推荐，猜你喜欢。
   * @apiParam {int}      [page=1]            搜索结果页码序号。
   * @apiParam {int}      [per_page=240]      搜索结果返回记录数量，因为是API调用，limit 应为前端每页显示条目的倍数。
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
    $query = app('request')->all();

    $origin   = ! empty($query[ 'origin' ]) ? mb_strtolower($query[ 'origin' ]) : 'mobile';
    $type     = ! empty($query[ 'type' ]) ? mb_strtolower($query[ 'type' ]) : 'list';
    $keywords = ! empty($query[ 'keywords' ]) ? trim($query[ 'keywords' ]) : null;
    $cid      = isset($query[ 'cid' ]) ? (int)$query[ 'cid' ] : null;
    $ids      = ! empty($query[ 'ids' ]) ? (array)$query[ 'ids' ] : null;
    $tag      = ! empty($query[ 'tag' ]) ? $query[ 'tag' ] : null;
    $page     = ! empty($query[ 'page' ]) ? (int)$query[ 'page' ] : 1;
    $per_page = ! empty($query[ 'per_page' ]) ? (int)$query[ 'per_page' ] : 240;

    //ddd($origin, $type, $keywords, $cid, $ids, $tag, $page, $per_page);

    if (is_null($cid) && is_null($keywords) && is_null($ids) && is_null($tag)) {
      throw new \Symfony\Component\HttpKernel\Exception\BadRequestHttpException('搜索请求参数必须含有以下其中一个：cid, keywords, ids, tag');
    }

    // 移动端搜索
    if ('mobile' === $origin) {
      if ('list' === $type) {
        if ($cid !== null) {
          return $this->getGoodsOfSearchListWithCategoryId($cid, $page, $per_page);
        }
      }

      if ('detail' === $type) {

      }
    }

    // PC 端搜索
    if ('pc' === $origin) {
      // TODO
    }
  }

  // 类目商品列表
  protected function getGoodsOfSearchListWithCategoryId($cid, $page, $per_page)
  {
    $data = CategoryRepository::getGoodsOfSearchListWithCategoryId($cid, $page, $per_page);

    return array (
      'total' => $data[ 'total' ],
      'page' => $data[ 'page' ],
      'per_page' => $data[ 'per_page' ],
      'goods' => $data[ 'goods' ],
    );
  }
}