<?php
namespace App\Api\V1\Goods\Category;

use App\Repositories\Goods\Category\CategoryRepository;

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
   * @apiSuccess  (响应失败) {array}      son         二级类目数据
   * @apiSuccess  (响应失败) {array}      end         叶子类目数据
   *
   * @apiSuccess  (响应失败) {int}        id          id
   * @apiSuccess  (响应失败) {int}        parent_id   父id
   * @apiSuccess  (响应失败) {string}     name        名称
   * @apiSuccess  (响应失败) {number}     sort        顺序
   * @apiSuccess  (响应失败) {string}     img         图片地址
   * @apiSuccess  (响应失败) {number}     url         链接地址，无商品类目只有单纯的链接
   */
  public function getTree()
  {
    $query = app('request')[ 'origin' ];
    $origin = $query ? mb_strtolower($query) : 'mobile';
    if ('mobile' === $origin) {
      return array (
        'categories' => CategoryRepository::getCategoryTreeOfMible()
      );
    }

    return '';  // TODO PC端
  }
}
