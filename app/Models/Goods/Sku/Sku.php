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


}