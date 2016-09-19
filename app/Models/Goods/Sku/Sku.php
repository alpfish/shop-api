<?php
namespace App\Models\Goods\Sku;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Goods\Sku\Attribute\SkuAttribute;
use App\Models\Goods\Sku\Relation\SkuRelation;

class Sku extends Model
{
    use SkuAttribute,
        SkuRelation;

    /**
     * 主键
     */
    protected $primaryKey = 'sku_id';

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

    // 此处不能设置 json 格式，否则 redis 无法 hash
    public $casts = [];
}