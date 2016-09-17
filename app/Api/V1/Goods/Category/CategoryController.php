<?php
namespace Api\V1\Goods\Category;

use App\Repositories\Goods\CategoryRepository;

class CategoryController
{
  /****************************************************************************************
   *
   * 获取类目树 API
   *
   * @by              Alpfish 2016/8/19 09:54
   * @api             {GET} goods/category/tree 类目树
   * @apiName         类目树
   * @apiGroup        Goods
   * @apiVersion      1.0.0
   * @apiPermission   public
   * @apiDescription  获取商品类目树，为三层树型结构。
   *
   * @apiParam    {string}  [origin=mobile]   请求来源, origin != mobile 则返回 PC 端类目展示列表数据
   *
   * @apiSuccess  (响应成功) {array}      son         子类目数组字段
   * @apiSuccess  (响应成功) {array}      end         叶类目数组字段
   *
   * *** 以下为各层级通用字段 ***
   *
   * @apiSuccess  (响应成功) {int}        id          id
   * @apiSuccess  (响应成功) {int}        parent_id   父id
   * @apiSuccess  (响应成功) {string}     name        名称
   * @apiSuccess  (响应成功) {number}     sort        顺序
   * @apiSuccess  (响应成功) {string}     img         图片地址
   * @apiSuccess  (响应成功) {number}     url         链接地址，无商品类目只有单纯的链接
   * @apiSuccess  (响应成功) {boolean}    end:push    是否推送/推荐（只有叶类目才有此字段）
   */
  public function getTree()
  {
    $query = app('request')[ 'origin' ];
    $origin = $query ? mb_strtolower($query) : 'mobile';
    if ('mobile' === $origin) {
      return array (
        'categories' => CategoryRepository::getTreeOfMobile()
      );
    }

    return '';  // TODO PC端
  }
}
