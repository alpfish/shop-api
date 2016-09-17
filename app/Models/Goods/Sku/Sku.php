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

    /**
     * 模型引导
     * The "booting" method of the model.
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        /**
         * 查询全局作用域
         *
         * 默认获取已上架 sku 的记录:
         *
         * Sku::all(); 生成的SQL语句为： select * from `goods_sku` where `status` = 1
         *
         * 查询时移除方法：
         * Cart::withoutGlobalScope('on_sale')->get();
         */
        static::addGlobalScope('on_sale', function(Builder $builder) {
            $builder->whereStatus(1);
        });
    }
}