<?php
/**
 * Prevent intruders from sneaking around
 */
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );


/**
 * WC ATCAA Public class
 */

class ATCAA_public
{

    const FORM_ID = "atcaa-return-reason-form";



    public static function atcaa_on_load()
    {
        add_action('plugins_loaded', array(__CLASS__, 'init'));
    }



    public static function init()
    {

        /* Add to cart on user login */
        add_action('wp', array(__CLASS__, 'atcaa_add_to_cart'));

        /* Delete from prepared items table on order submit */
        add_action('save_post_shop_order', array(__CLASS__, 'atcaa_delete_from_prepared_items_table'));

    }


    /*
     * Add items to customer's cart on customer's login
     */
    public static function atcaa_add_to_cart()
    {
        if ( ! is_admin() ) {

            global $wpdb;
            global $woocommerce;

            $current_user = wp_get_current_user();
            if (!$current_user->exists()) {
                return;
            }

            $query = "SELECT * FROM {$wpdb->prefix}atcaa_prepared_items WHERE user_id = '$current_user->id' AND imported_to_cart = '0'";
            $results = $wpdb->get_results($query);


            if (isset($results)) {
                foreach ($results as $result) {
                    $woocommerce->cart->add_to_cart($result->product_id, $result->quantity, $result->variation_id);
                    $query = "UPDATE {$wpdb->prefix}atcaa_prepared_items SET imported_to_cart = '1' WHERE product_id = '$result->product_id' AND user_id = '$current_user->id'";
                    $wpdb->get_results($query);
                }
            }
        }
    }



    /*
     * Delete from prepared items table on order submit
     * */
    public static function atcaa_delete_from_prepared_items_table() {

        global $wpdb;

        $current_user = wp_get_current_user();
        if ( ! $current_user->exists() ) {
            return;
        }

        $query = "DELETE FROM {$wpdb->prefix}atcaa_prepared_items WHERE imported_to_cart = '1' AND user_id = '$current_user->id'";
        $wpdb->get_results($query);
    }

}