<?php
class AWPCustomDiscount
{
    private $coupon_code;

    public function __construct(
        $coupon_code,
        $amount,
        $discount_type = 'fixed_cart'
    ) {
        $this->coupon_code = $coupon_code;
        $this->createCoupon($amount, $discount_type);

        add_action('woocommerce_before_cart', [$this, 'addDiscount']);
        add_action('woocommerce_before_checkout_form', [$this, 'addDiscount']);
        add_filter(
            'woocommerce_cart_totals_coupon_label',
            [$this, 'discountLabel'],
            10,
            2
        );
    }

    function discountLabel($label, $coupon)
    {
        if ($coupon->code == $this->coupon_code) {
            return __('Giftback');
        }
        return $label;
    }

    function addDiscount($order)
    {
        $order->apply_coupon($this->coupon_code);
        $order->save();
    }

    function createCoupon($amount, $discount_type = 'fixed_cart')
    {
        $coupon = [
            'post_title' => $this->coupon_code,
            'post_content' => '',
            'post_status' => 'publish',
            'post_author' => 1,
            'post_type' => 'shop_coupon',
        ];

        $new_coupon_id = wp_insert_post($coupon);

        // Add meta
        update_post_meta($new_coupon_id, 'discount_type', $discount_type);
        update_post_meta($new_coupon_id, 'coupon_amount', $amount);
        update_post_meta($new_coupon_id, 'individual_use', 'yes');
        update_post_meta($new_coupon_id, 'usage_limit', 1);
        update_post_meta($new_coupon_id, 'product_ids', '');
        update_post_meta($new_coupon_id, 'exclude_product_ids', '');
        update_post_meta($new_coupon_id, 'usage_limit', '');
        update_post_meta($new_coupon_id, 'expiry_date', '');
        update_post_meta($new_coupon_id, 'apply_before_tax', 'yes');
        update_post_meta($new_coupon_id, 'free_shipping', 'no');
    }
}
