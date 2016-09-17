<?php
namespace App\Models\Promotion\GoodsPromotion;

use Illuminate\Database\Eloquent\Model;

class GoodsPromotion extends Model
{
    protected $table = 'promotion_goods';

    protected $guarded = [ 'id' ];

    public $timestamps = false;

    protected $casts
        = [
            // 以数组/对象形式存取json字段: rules
            // 例子：
            // $prom = GoodsPromotion::find(3);
            // echo $prom->rules[ 0 ][ 'type' ];
            // http://laravel.so/tricks/dd34aab05e282a0a2c4cc320e14ae936
            'rules' => 'json',
        ];
}