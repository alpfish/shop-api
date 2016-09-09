<?php
namespace App\Models\Goods\Goods;

use Illuminate\Database\Eloquent\Model;
use App\Models\Goods\Goods\Attribute\GoodsAttribute;
use App\Models\Goods\Goods\Relation\GoodsRalation;

class Goods extends Model
{
    use GoodsAttribute, GoodsRalation;

    /**
     * 表名
     */
    protected $table = 'goods_spu';

    /**
     * 隐藏字段
     */
    protected $hidden = ['status', 'style', 'keyword', 'description'];

    /**
     * 追加字段
     */
    protected $appends = [
        'is_sale',          // 出售中 （由 status 计算而来，更加语义化）
        'orig',             // 原价
        'price',            // 销售价
        'quantity',         // 数量
        'sales',            // 销量
        'only_sku',         // 单一SKU
        'only_sku_id',      // 单一SKU id
        'tag_thumb',        // 缩略图标签
        'tag_prom',         // 优惠促销标签
        'features'          // 特征卖点标签
    ];

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