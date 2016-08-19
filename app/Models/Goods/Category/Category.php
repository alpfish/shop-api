<?php

namespace App\Models\Goods\Category;

use Illuminate\Database\Eloquent\Model;
use App\Models\Goods\Category\Attribute\CategoryAttribute;
use App\Models\Goods\Category\Relation\CategoryRelation;

class Category extends Model
{
    use CategoryAttribute,
        CategoryRelation;

    protected $table = 'goods_category';

    protected $hidden = [

    ];

    protected $appends = [

    ];

}