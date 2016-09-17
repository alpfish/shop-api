<?php


namespace App\Repositories\Cart;

use App\Models\Cart\Cart;
use App\Models\Goods\Sku\Sku;
use App\Repositories\Promotion\TimePromotionRepository as TimePromotion;
use App\Repositories\Promotion\OrderPromotionRepository as OrderPromotion;
use App\Repositories\Promotion\GoodsPromotionRepository as GoodsPromotion;

/**
 * 购物车设计
 * 1. 前后端数据一致性原则，如：购物车数据，结算总价的展示与存储必须一致。
 * 这就需要在后端统一存储购物车数据，前端每进入购物车使用AJAX请求同步数据，add/update/delete都使用AJAX请求，
 * 另外，前端不作结算方面的处理。
 * 2. 最大限度保持数据同步并尽量减少请求次数
 * 避免受服务端响应延迟影响，在前端展示发生明显的操作回退。如：连续点击减少商品数量从20到10件后，因为多次请求和
 * 响应延时使得商品数量回退到非10件(如15)，对顾客造成困惑。
 * 解决：在前端控制请求的时间间隔（如 300-500ms）和监听同一商品的操作频率, 最后只进行一次请求更新操作。
 * 批量删除也只进行一次AJAX请求。
 * */
class CartRepository
{
    static public function all()
    {
        return Cart::all();
    }

    /**
     * 添加购物车商品
     *
     * 1. 数量必须为正数
     * 2. 商品存在时更新
     * 3. 不支持数组，HTTP请求以KV字符串形式传参，如支持数组前后端需要序列化处理, 数组在添加组合商品才有用
     *
     * @param int $sku_id   单SKU
     * @param int $quantity 数量
     *
     * @throws
     * @return  \App\Models\Cart\Cart 实例
     * Author AlpFish 2016/9/12
     */
    static public function add($sku_id, $quantity)
    {
        // TODO 使用缓存
        $sku = Sku::select('number')->find($sku_id);
        if (!$sku) {
            throw new \Dingo\Api\Exception\ValidationHttpException([ 'sku_id' => '商品已下架。' ]);
        }
        $cart = Cart::whereSkuId($sku_id)->first();
        if ($sku->number < ( $quantity + (int)$cart[ 'nums' ] )) {
            throw new \Dingo\Api\Exception\ValidationHttpException([ 'quantity' => '购买数量大于库存数量。' ]);
        }
        try {
            // 存在时更新
            if ($cart) {
                $cart->nums = (int)$quantity;
                $cart->save();
            } else{
                // 添加
                $cart              = new Cart;
                $cart->buyer_id    = auth_member()->id;
                $cart->sku_id      = $sku_id;
                $cart->nums        = (int)$quantity;
                $cart->clientip    = app('request')->ip();
                $cart->system_time = time();
                $cart->save();
            }
        } catch (\Exception $e) {
            throw new \Exception('添加购物车商品失败', 500);
        }

        return $cart;
    }

    /**
     * 更新购物车商品数量
     *
     * 为何使用 $cart_id ?
     * 购物车增/删/改涉及频繁的AJAX请求，使用主键提升查询效率。
     *
     * @param int $cart_id  购物车id
     * @param int $quantity 数量
     *
     * @return boolean
     *
     * Author AlpFish 2016/9/13
     */
    static public function update($cart_id, $quantity)
    {
        // 未判断库存，在获取购物车时带库存量&&前端判断&&后台结算时判断
        return Cart::select('nums')->whereId($cart_id)->update([ 'nums' => $quantity ]);
    }

    /**
     * 删除购物车商品
     *
     * @param int|array $cart_id
     *
     * @return boolean
     *
     * Author AlpFish 2016/9/13
     */
    static public function delete($cart_id)
    {
        return Cart::whereIn('id', (array)$cart_id)->delete();
    }

    /**
     * 购物车条目结算
     *
     * 只考虑单店系统
     * 后台自动计算运费
     *
     * 商品促销：支持按件/元打折, 最高50%OFF，在后台设置满1元减0.XX元即可。
     * 订单促销：支持打折(已参加促销的商品除外)，最高50%OFF，在后台设置满1元减0.XX元即可。
     *
     * @param array   $cart_items    购物车条目, 每行至少需要 sku_id, quantity 两个属性，[join_prom]参与商品促销属性可选，
     *                               默认参与, 多出的属性(如购物车id)在调用参数 query_fields 为不为空时会自动返回。
     *                               eg: [ ['sku_id'=>'1', 'quantity'=>'1', 'prom_id'=>'false'],
     *                               [...] ]
     * @param int     $order_prom_id 顾客参与的订单促销id，默认为空，表示系统自动选择
     * @param boolean $invoices      是否开具发票
     * @param array   $query_fields  是否附加返回购物车条目, 默认只返回结算数据。如需要返回条目，需要指定要返回哪些 sku 字段
     *
     * @return array
     * @throws
     * Author AlpFish 2016/9/14
     */
    static public function settlement(array $cart_items, $order_prom_id = null, $invoices = false, $query_fields = [ ])
    {
        // 初始运费
        $freight             = env('SHOP_FREIGHT', 3);  # 统一运费
        $freight_free_amount = env('SHOP_FREIGHT_FREE_AMOUNT', 30); # 包邮金额

        // 初始数据
        $settle = [
            'settlement'       => [
                'sku_amount'      => 0, #商品总额
                'settle_amount'   => 0, #结算总额 = 商品总额 - 优惠总额
                'discount_amount' => 0, #优惠总额 = 商品优惠 + 订单优惠
                'invoice_tax'     => 0, #税费
                'freight'         => $freight, #运费
            ],
            'order_promotions' => [ ],
        ];

        if (empty( $cart_items )) {
            return $settle;
        }
        if (!isset( $cart_items[ 0 ][ 'sku_id' ] ) || !isset( $cart_items[ 0 ][ 'quantity' ] )) {
            throw new \Exception('无效的购物车参数.');
        }

        // 获取SKU数据
        $field_fixed = [ 'sku_id', 'shop_price', 'prom_type', 'prom_id' ];
        $skus        = Sku::select(array_merge($field_fixed, (array)$query_fields))
            ->whereIn('sku_id', collect($cart_items)->pluck('sku_id'))->get()->toArray();

        // 合并购买数据
        foreach ($cart_items as $key => $item){
            foreach ($skus as $k => $v){
                if ($v[ 'sku_id' ] === $item[ 'sku_id' ]) {
                    $cart_items[ $key ] = array_merge($cart_items[ $key ], $skus[ $k ]);
                    break;
                }
            }
        }

        // 合并商品促销数据
        foreach ($cart_items as $key => $item){

            // 初始化
            $cart_items[ $key ][ 'prom_name' ]     = ''; # 促销名称
            $cart_items[ $key ][ 'prom_price' ]    = $item[ 'shop_price' ]; // 促销价
            $cart_items[ $key ][ 'settle_amount' ] = $item[ 'shop_price' ] * $item[ 'quantity' ]; # 结算总额
            $cart_items[ $key ][ 'share_order' ]   = true;  # 同享订单促销
            $cart_items[ $key ][ 'discounted' ]    = 0;     # 已打折金额
            $cart_items[ $key ][ 'gift' ]          = '';    # 赠品

            $join_prom = true;
            if (isset( $item[ 'join_prom' ] ) && !$item[ 'join_prom' ]) {
                $join_prom = false;
            }
            if ($join_prom && $item[ 'prom_type' ]) {
                switch (strtolower($item[ 'prom_type' ])){
                    // 限时促销
                    case 'time' : {
                        $settle = TimePromotion::settlement($item[ 'prom_id' ], $item[ 'sku_id' ], $item[ 'shop_price' ]);

                        $cart_items[ $key ][ 'prom_name' ]     = $settle[ 'prom_name' ];
                        $cart_items[ $key ][ 'prom_price' ]    = $settle[ 'settle_price' ];
                        $cart_items[ $key ][ 'settle_amount' ] = $settle[ 'settle_price' ] * $item[ 'quantity' ];
                        $cart_items[ $key ][ 'share_order' ]   = false;
                        $cart_items[ $key ][ 'discounted' ]    = ( $item[ 'shop_price' ] - $settle[ 'settle_price' ] ) * $item[ 'quantity' ];
                        break;
                    }
                    // 商品促销
                    case 'goods' : {
                        $settle = GoodsPromotion::settlement($item[ 'prom_id' ], ( $item[ 'shop_price' ] * $item[ 'quantity' ] ), $item[ 'quantity' ]);

                        $cart_items[ $key ][ 'prom_name' ]     = $settle[ 'prom_name' ];
                        $cart_items[ $key ][ 'settle_amount' ] = $settle[ 'settle_amount' ];
                        $cart_items[ $key ][ 'share_order' ]   = $settle[ 'share_order' ];
                        $cart_items[ $key ][ 'discounted' ]    = $settle[ 'discounted' ];
                        $cart_items[ $key ][ 'gift' ]          = self::getGift($settle[ 'gift' ]);
                    }
                }
            }
        }

        $items = collect($cart_items);

        // 获取订单促销
        $amount      = $items->sum(function($v){ return $v[ 'share_order' ] ? $v[ 'settle_amount' ] : 0; });
        $order_proms = OrderPromotion::settlement($amount, $order_prom_id);
        foreach ($order_proms as $key => $value){
            $value[ 'gift' ] = self::getGift($value[ 'gift' ]);
            $order_proms->put($key, $value);
        }

        $order_prom = $order_proms->first();

        // 原价总额 = sum( 商品单价 * 购买数量 )
        $sku_amount = $items->sum(function($item){ return $item[ 'shop_price' ] * $item[ 'quantity' ]; });

        // 优惠总额 = 商品优惠 + 订单优惠
        $discounted = $items->sum('discounted') + $order_prom[ 'discounted' ];

        // 运费结算
        $freight = isset( $order_prom[ 'freight' ] ) ? $order_prom[ 'freight' ] : $freight; # 订单促销免邮
        $freight = ( $sku_amount - $discounted ) >= $freight_free_amount ? 0 : $freight; # 系统免邮规则

        // 税收
        $invoice_tax = 0;
        try {
            if ($invoices) {
                $tax_rate    = app('db')->table('setting')->whereKey('invoice_tax')->first()->value;
                $invoice_tax = ( $sku_amount - $discounted ) * $tax_rate * 0.01;
            }
        } catch (\Exception $e) {
            // nothing
        }

        // 结算总额 = 原价总额 + 运费 + 税收- 优惠总额
        $settle_amount = $sku_amount + $freight + $invoice_tax - $discounted;

        // 组装数据
        $out = [
            // 购物车结算
            'settlement'       => [
                'sku_amount'    => sprintf("%.2f", $sku_amount), # 商品总额
                'settle_amount' => sprintf("%.2f", $settle_amount), # 结算总额 = 商品总额 - 优惠总额 + 税收
                'discounted'    => sprintf("%.2f", $discounted), # 优惠总额 = 商品优惠 + 订单优惠
                'invoice_tax'   => sprintf("%.2f", $invoice_tax), #税费
                'freight'       => (int)$freight, #运费
            ],
            // 订单促销
            'order_promotions' => $order_proms,
        ];

        // 商品条目
        if ($query_fields) {
            $out[ 'skus' ] = &$items;
        }

        return $out;
    }

    // TODO 赠品在哪里获取，赠品怎样减少查询次数？
    static public function getGift($id)
    {
        static $gifts = [ ];
        if (empty($id)) {
            return null;
        }
        if (!isset( $gifts[ $id ] )) {
            $gifts[ $id ] = Sku::find($id);
        }
        if ($gifts[ $id ]) {
            return [
                'id'    => $id,
                'name'  => $gifts[ $id ][ 'sku_name' ],
                'thumb' => $gifts[ $id ][ 'thumb' ],
            ];
        } else{
            return null;
        }
    }

}