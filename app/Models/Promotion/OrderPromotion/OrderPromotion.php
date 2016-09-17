<?php

namespace App\Models\Promotion\OrderPromotion;

use Illuminate\Database\Eloquent\Model;

class OrderPromotion extends Model
{
    protected $table = 'promotion_order';

    protected $guarded = [ 'id' ];

    public $timestamps = false;

}