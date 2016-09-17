<?php
namespace App\Models\Promotion\TimePromotion;

use Illuminate\Database\Eloquent\Model;

class TimePromotion extends Model
{
    protected $table = 'promotion_time';

    protected $guarded = [ 'id' ];

    public $timestamps = false;

    protected $casts
        = [
            // 以数组/对象形式存取json字段: sku_info
            // 例子：
            // $prom = GoodsPromotion::find(3);
            // echo $prom->sku_info[$sku_id];
            // http://laravel.so/tricks/dd34aab05e282a0a2c4cc320e14ae936
            'sku_info' => 'json',
        ];
}