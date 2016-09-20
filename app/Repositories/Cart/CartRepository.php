<?php
namespace App\Repositories\Cart;

use App\Models\Cart\Cart;
use App\Exceptions\ApiException;
use App\Repositories\Goods\SkuRepository;
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
    /**
     * 添加购物车商品
     *
     * 1. 数量必须为正数
     * 2. 商品存在时直接更新数量为 buy_nums ，不进行自增计算，自增请在前台进行
     * 3. 不支持数组，HTTP请求以KV字符串形式传参，如支持数组前后端需要序列化处理, 数组在添加组合商品才有用
     *
     * @param int $sku_id   单SKU
     * @param int $buy_nums 数量
     *
     * @throws
     * @return  \App\Models\Cart\Cart 实例
     * Author AlpFish 2016/9/12
     */
    static public function add($sku_id, $buy_nums)
    {
        $sku = SkuRepository::find($sku_id, 'number', false);
        if (empty($sku[ 0 ][ 'number' ])) {
            throw new ApiException([ 'sku_id' => '商品已下架。' ], 422);
        }
        $cart = Cart::whereSkuId($sku_id)->first();
        if ($sku[ 0 ][ 'number' ] < $buy_nums) {
            throw new ApiException([ 'buy_nums' => '商品库存不足。' ], 422);
        }
        try {
            // 存在时更新
            if ($cart) {
                $cart->nums = (int)$buy_nums;
                $cart->save();
            } else{
                // 添加
                $cart              = new Cart;
                $cart->buyer_id    = auth_member()->id;
                $cart->sku_id      = $sku_id;
                $cart->nums        = (int)$buy_nums;
                $cart->clientip    = app('request')->ip();
                $cart->system_time = time();
                $cart->save();
            }
        } catch (\Exception $e) {
            throw new \Exception('添加购物车商品失败');
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
     * @param int $buy_nums 数量
     *
     * @return boolean
     * @throws
     * AlpFish 2016/9/13
     */
    static public function update($cart_id, $buy_nums)
    {
        if (!is_numeric($buy_nums) || $buy_nums < 1) {
            throw new ApiException([ 'buy_nums' => '商品数量不正确。' ], 422);
        }

        // 未判断库存，在获取购物车时带库存量&&前端判断&&后台结算时判断
        return Cart::select('nums')->whereId($cart_id)->update([ 'nums' => (int)$buy_nums ]);
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
     * 获取格式化的购物车商品结算信息
     *
     * @param array|int $cart_ids 购物车条目
     *
     * @throws
     * @return array | null
     * AlpFish 2016/9/19
     */
    static public function getSkuSettleInfo($cart_ids = [ ])
    {
        // 获取购物车条目
        $cart_items = self::getCartItems($cart_ids);
        if (empty( $cart_items )) {
            return null;
        }

        // 添加SKU数据
        $items = self::addSkuDataToCartItems($cart_items);

        // 添加商品级促销数据
        $items = self::addPromotionToCartItems($items);

        // 格式化输出前台数据
        return self::formatOutputCartItems($items);
    }

    /**
     * 购物车条目结算
     *
     * 只考虑单店系统
     * 根据购物车ID结算, 立即购买要在此功能上扩展
     * [顾客规则]:
     * 商品/限时促销固定不可取消。
     * 已参加商品促销后不再参加订单促销中的打折促销，可参加订单促销中的满减满送。
     * 包邮促销为全店促销，与其它促销同时享受。
     * 系统不能更改顾客数据原则（下架/无货商品由顾客自己删除）
     *
     * 商品促销：支持按件/元打折, 最高50%OFF，在后台设置满1元减0.XX元即可。
     * 订单促销：支持打折，最高50%OFF，在后台设置满1元减0.XX元即可。
     *
     * @param array   $cart_ids      会员购物车ids，为空表示所有商品
     * @param int     $order_prom_id 顾客参与的订单促销id，默认为空，表示系统自动选择
     * @param boolean $invoices      是否开具发票
     * @param boolean $check_stock   是否检查库存，库存小于购买数将抛出异常
     *
     * @return array | null
     * @throws
     * Author AlpFish 2016/9/14
     */
    static public function settlement(array $cart_ids = [ ], $order_prom_id = null, $invoices = false, $check_stock = false)
    {

        // 初始运费
        $freight             = env('SHOP_FREIGHT', 3);  # 统一运费
        $freight_free_amount = env('SHOP_FREIGHT_FREE_AMOUNT', 30); # 包邮金额

        // 获取购物车条目
        $cart_items = self::getCartItems($cart_ids);
        if (empty( $cart_items )) {
            return null;
        }

        // 添加SKU数据
        $items = self::addSkuDataToCartItems($cart_items);

        // 添加商品级促销数据
        $items = collect(self::addPromotionToCartItems($items));

        // 原价总额 = sum( 商品单价 * 购买数量 )
        $sku_amount = $items->sum(function($item){ return $item[ 'shop_price' ] * $item[ 'buy_nums' ]; });

        // 获取订单促销
        $amount      = $items->sum(function($v){ return $v[ 'share_order' ] ? $v[ 'settle_amount' ] : 0; });
        $order_proms = OrderPromotion::settlement($amount, $order_prom_id, $sku_amount);
        foreach ($order_proms as $key => $value){
            $value[ 'gift' ] = self::getGift($value[ 'gift' ]);
            $order_proms->put($key, $value);
        }

        $order_prom = $order_proms->first();

        // 优惠总额 = 商品优惠 + 订单优惠
        $discounted = $items->sum('discounted') + $order_prom[ 'discounted' ];

        // 运费结算
        $freight = isset( $order_prom[ 'freight' ] ) ? $order_prom[ 'freight' ] : $freight; # 订单促销免邮
        $freight = ( $sku_amount - $discounted ) >= $freight_free_amount ? 0 : $freight; # 系统免邮规则

        // 税收
        $invoice_tax = 0;
        if ($invoices) {
            $tax_rate    = app('db')->table('setting')->whereKey('invoice_tax')->first()->value;
            $invoice_tax = ( $sku_amount - $discounted ) * $tax_rate * 0.01;
        }

        // TODO 积分/优惠券

        // 结算总额 = 原价总额 + 运费 + 税收- 优惠总额
        $settle_amount = $sku_amount + $freight + $invoice_tax - $discounted;
        // 组装数据
        $out = [
            // 结算数据
            'settlement'  => [
                'sku_amount'    => sprintf("%.2f", $sku_amount),    # 商品总额
                'settle_amount' => sprintf("%.2f", $settle_amount), # 结算总额 = 商品总额 - 优惠总额 + 税收
                'discounted'    => sprintf("%.2f", $discounted),    # 优惠总额 = 商品优惠 + 订单优惠
                'invoice_tax'   => sprintf("%.2f", $invoice_tax),   # 税费
                'freight'       => sprintf("%.2f", $freight),       # 运费
                'count'         => $items->sum('buy_nums'),         # 购买数量
            ],
            // 订单促销
            'order_proms' => $order_proms,
            // 商品数据
            'cart_items'  => self::formatOutputCartItems($items),
        ];

        return $out;
    }

    /**
     * 统一获取购物车条目
     *
     * @param array|int [$cart_ids] 购物车id, 默认获取所有
     *
     * @return array
     *
     * Author AlpFish 2016/9/19
     */
    static private function getCartItems($cart_ids = [ ])
    {
        if (empty( $cart_ids )) {
            $cart_items = Cart::select('id', 'sku_id', 'nums as buy_nums')->get()->toArray();
        } else{
            $cart_items = Cart::select('id', 'sku_id', 'nums as buy_nums')->whereIn('id', (array)$cart_ids)->get()->toArray();
        }

        return $cart_items;
    }

    /**
     * 添加统一格式的SKU数据到购物车条目
     *
     * @param array $cart_items
     *
     * @return array|null
     * @throws
     *
     * Author AlpFish 2016/9/19
     */
    static private function addSkuDataToCartItems($cart_items)
    {
        if (empty( $cart_items )) {
            return null;
        }
        $items = collect($cart_items)->first();
        if (!isset( $items[ 'id' ] ) || !isset( $items[ 'sku_id' ] ) || !isset( $items[ 'buy_nums' ] )) {
            throw new \Exception('购物车条目每行需要以下三个属性：id, sku_id, buy_nums');
        }
        $sku_ids = collect($cart_items)->pluck('sku_id')->all();
        $fields  = [ 'sku_id', 'shop_price', 'number', 'prom_id', 'prom_type', 'status', 'sku_name', 'thumb', 'spec' ];
        $skus    = SkuRepository::find($sku_ids, $fields, $cache = false); # 获取所有状态的SKU

        foreach ($cart_items as $key => $value){# cartitems为外循环，目的是不修改购物车中已下架商品，由顾客自己删除
            $found = false;
            foreach ($skus as $k => $v){
                if ($value[ 'sku_id' ] == $v[ 'sku_id' ]) {
                    $cart_items[ $key ]                = array_merge($cart_items[ $key ], $skus[ $k ]);
                    $cart_items[ $key ][ 'is_onsale' ] = $skus[ $k ][ 'status' ] == 1 ? true : false; # 下架或软删除
                    $found                             = true;
                    break;
                }
            }
            if (!$found) { # 删除物理不存在的商品
                Cart::whereSkuId($cart_items[ $key ][ 'sku_id' ])->delete();
                unset( $cart_items[ $key ] );
            }
        }

        return $cart_items;
    }

    /**
     * 添加促销数据到购物车条目
     *
     * @param array $cart_items
     *
     * @return array
     *
     * Author AlpFish 2016/9/19
     */
    static private function addPromotionToCartItems($cart_items)
    {
        if (empty( $cart_items )) {
            return null;
        }
        foreach ($cart_items as $key => $item){

            // 初始化
            $cart_items[ $key ][ 'prom_name' ]     = ''; # 促销名称
            $cart_items[ $key ][ 'prom_price' ]    = $item[ 'shop_price' ]; // 促销价
            $cart_items[ $key ][ 'settle_amount' ] = $item[ 'shop_price' ] * $item[ 'buy_nums' ]; # 结算总额(设置该属性原因：在商品促销中总额不一定等于价格乘数量)
            $cart_items[ $key ][ 'share_order' ]   = true;  # 同时享受订单促销
            $cart_items[ $key ][ 'discounted' ]    = 0;     # 已打折金额
            $cart_items[ $key ][ 'gift' ]          = '';    # 赠品

            if ($item[ 'prom_type' ]) {
                switch (strtolower($item[ 'prom_type' ])){
                    // 限时促销
                    case 'time' : {
                        $settle = TimePromotion::settlement($item[ 'prom_id' ], $item[ 'sku_id' ], $item[ 'shop_price' ]);

                        $cart_items[ $key ][ 'prom_name' ]     = $settle[ 'prom_name' ];
                        $cart_items[ $key ][ 'prom_price' ]    = $settle[ 'settle_price' ];
                        $cart_items[ $key ][ 'settle_amount' ] = $settle[ 'settle_price' ] * $item[ 'buy_nums' ];
                        $cart_items[ $key ][ 'share_order' ]   = false;
                        $cart_items[ $key ][ 'discounted' ]    = ( $item[ 'shop_price' ] - $settle[ 'settle_price' ] ) * $item[ 'buy_nums' ];
                        break;
                    }
                    // 商品促销
                    case 'goods' : {
                        $settle = GoodsPromotion::settlement($item[ 'prom_id' ], ( $item[ 'shop_price' ] * $item[ 'buy_nums' ] ), $item[ 'buy_nums' ]);

                        $cart_items[ $key ][ 'prom_name' ]     = $settle[ 'prom_name' ];
                        $cart_items[ $key ][ 'settle_amount' ] = $settle[ 'settle_amount' ];
                        $cart_items[ $key ][ 'share_order' ]   = $settle[ 'share_order' ];
                        $cart_items[ $key ][ 'discounted' ]    = $settle[ 'discounted' ];
                        $cart_items[ $key ][ 'gift' ]          = self::getGift($settle[ 'gift' ]);
                    }
                }
            }
        }

        return $cart_items;
    }

    /**
     * 格式化输出购物车条目
     *
     * @param array $cart_items 添加SKU数据和商品促销数据后的购物车条目
     *
     * @return array
     *
     * Author AlpFish 2016/9/19
     */
    static private function formatOutputCartItems($cart_items)
    {
        return collect($cart_items)->map(function($item){
            return [
                'id'            => (int)$item[ 'id' ],
                'sku_id'        => (int)$item[ 'sku_id' ],
                'buy_nums'      => (int)$item[ 'buy_nums' ],
                'sku_nums'      => (int)$item[ 'number' ],
                'sku_name'      => $item[ 'sku_name' ],
                'sku_spec'      => collect(json_decode($item[ 'spec' ]))->map(function($v){ return [ $v->name => $v->value ]; }),
                'sku_thumb'     => $item[ 'thumb' ],
                'is_onsale'     => $item[ 'is_onsale' ],
                'shop_price'    => sprintf("%.2f", $item[ 'shop_price' ]),
                'prom_price'    => sprintf("%.2f", $item[ 'prom_price' ]),
                'prom_id'       => (int)$item[ 'prom_id' ],
                'prom_type'     => $item[ 'prom_type' ],
                'prom_name'     => $item[ 'prom_name' ],
                'share_order'   => $item[ 'share_order' ],
                'settle_amount' => sprintf("%.2f", $item[ 'settle_amount' ]),
                'discounted'    => sprintf("%.2f", $item[ 'discounted' ]),
                'gift'          => $item[ 'gift' ],
            ];
        })->keyBy('id')->all();
    }

    /**
     * 获取赠品数据
     *
     * @param int $sku_id
     *
     * @return array|''
     *
     * Author AlpFish 2016/9/19
     */
    static private function getGift($sku_id)
    {
        if (empty( $sku_id )) {
            return '';
        }
        $gift = SkuRepository::find($sku_id, [ 'sku_id', 'sku_name', 'number', 'status' ], $cache = true)[ 0 ];
        if ($gift && ( $gift[ 'number' ] < 1 || $gift[ 'status' ] != 1 )) {
            return '';
        }

        return [
            'sku_id' => $gift[ 'sku_id' ],
            'name'   => $gift[ 'sku_name' ],
        ];
    }

}