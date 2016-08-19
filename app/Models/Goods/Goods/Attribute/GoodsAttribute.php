<?php
namespace App\Models\Goods\Goods\Attribute;


trait GoodsAttribute
{
    /**
     * 是否在售
     */
    public function getIsSaleAttribute()
    {
        return $this->attributes[ 'status' ] === 1;
    }

    /**
     * SPU 原价
     */
    public function getOrigAttribute()
    {
        return $this->sku()->min('market_price');
    }

    /**
     * SPU 价格
     */
    public function getPriceAttribute()
    {
        return $this->attributes[ 'min_price' ];
    }

    /**
     * SPU 数量
     */
    public function getQuantityAttribute()
    {
        return $this->attributes[ 'sku_total' ];
    }

    /**
     * SPU 销量
     */
    public function getSalesAttribute()
    {
        $sales = app('db')->table('goods_index')->where('spu_id', $this->attributes['id'])->sum('sales');

        return $sales;
    }

    /**
     * 搜索质量得分
     */
    public function getSortAttribute()
    {
        $sort = $this->attributes['sort'] * 2;

        return $sort;
    }

    /**
     * 是否单一 SKU
     */
    public function getOnlySkuAttribute()
    {
        return 1 === $this->sku()->count();
    }

    /**
     * 单一 SKU id
     */
    public function getOnlySkuIdAttribute()
    {
        if (1 === $this->sku()->count()) {
            return $this->sku()->first()->sku_id;
        };
        return null;
    }

    /**
     * 缩略图标签
     */
    public function getTagThumbAttribute()
    {
        $ext = $this->sku()->where('status_ext', '>', 0)->first();
        $tag_thumb = null;
        if ($ext) {
            switch ($ext->status_ext) {
                case 1:
                    $tag_thumb = '促销'; break;
                case 2:
                    $tag_thumb = '热卖'; break;
                case 3:
                    $tag_thumb = '新品'; break;
                case 4:
                    $tag_thumb = '推荐'; break;
                default:
                    $tag_thumb = null;
            }
        }
        return $tag_thumb;
    }

    /**
     * 促销标签
     *
     * 目前SKU级别的促销有：商品促销 和 限时促销；另外还有订单促销， 组合营销不是促销。
     */
    public function getTagPromAttribute()
    {
        $prom = $this->sku()->where('prom_id', '<>', 0)->first();
        $tag = null;
        if ($prom) {
            switch ($prom->prom_type) {
                case 'goods' :
                    $tag = $prom->goods_promotion()->first()->name; break;
                case 'time' :
                    $tag = $prom->time_promotion()->first()->name; break;
            }
        }

        return $tag;
    }

    /**
     * SPU 特征标签
     *
     * 卖点什么的，暂以写在副标题的内容，以后再扩充内容。
     * */
    public function getFeaturesAttribute()
    {
        return $this->attributes['subtitle'];
    }

    /**
     * 商品图册url处理
     */
    //    public function getImgsAttribute() {
    //        $root = 'http://192.168.0.108/shop/';
    //        $jsonUrl = str_replace('\/', '/', $this->attributes['imgs']);
    //        return $jsonUrl;
    //    }

    /**
     * 商品缩略图
//     */
    //    public function getThumbAttribute()
    //    {
    //        $root = 'http://192.168.0.108/shop/';
    //
    //        return str_replace('./', $root, $this->attributes[ 'thumb' ]);
    //    }


}