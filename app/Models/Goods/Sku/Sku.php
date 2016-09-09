<?php
namespace App\Models\Goods\Sku;

use Illuminate\Database\Eloquent\Model;
use App\Models\Goods\Sku\Attribute\SkuAttribute;
use App\Models\Goods\Sku\Relation\SkuRelation;
class Sku extends Model
{
    use SkuAttribute,
        SkuRelation;

    /**
     * 表名称
     */
    protected $table = 'goods_sku';

    /**
     * 禁用时间戳
     *
     * 禁用 Lumen 自带的 created_at 和 updated_at 两个时间戳字段，
     * 表中没有这两个字段，禁用后插入/更新记录才能成功。
     *
     * @var bool
     */
    public $timestamps = false;


}