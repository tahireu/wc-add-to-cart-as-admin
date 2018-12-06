<?php
/**
 * Prevent intruders from sneaking around
 */
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );


/**
 * WC Add To Cart As Admin Activator class
 */

class ATCAA_activator
{

    public static $atcaa_db_version = '1.0';


    /*
     * Create database table on plugin activation
     * */
    public static function atcaa_create_table() {

        global $wpdb;
        $table_name = $wpdb->prefix . 'atcaa_prepared_items';
        $charset_collate = $wpdb->get_charset_collate();

        $query = "CREATE TABLE $table_name (
id int(11) NOT NULL AUTO_INCREMENT,
user_id int(11) NOT NULL,
product_id int(11) NOT NULL,
variation_id int(11) DEFAULT NULL,
imported_to_cart tinyint(1) DEFAULT '0' NOT NULL,
ordered tinyint(1) DEFAULT '0' NOT NULL,
quantity int(11) DEFAULT NULL,
PRIMARY KEY  (id)
) $charset_collate;";

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $query );

        add_option( 'atcaa_db_version', self::$atcaa_db_version );
    }



    /*
     * If WooCommerce is not installed and activated abort everything and display error message
     * */
    public static function atcaa_abort()
    {
        deactivate_plugins('/wc-add-to-cart-as-admin/wc-add-to-cart-as-admin.php');
        add_action('admin_notices', array(__CLASS__, 'atcaa_display_admin_notice_error'));
    }



    /*
     * Display our error admin notice if WooCommerce is not installed and active
     */
    public static function atcaa_display_admin_notice_error()
    {
        ?>
        <!-- hide the 'Plugin Activated' default message -->
        <style>
            #message.updated {
                display: none;
            }
        </style>
        <!-- display error message -->
        <div class="error">
            <p>
                <b><?php echo __('WC Add To Cart As Admin plugin could not be activated because WooCommerce is not installed and active.', ATCAA_TEXT_DOMAIN); ?></b>
            </p>
            <p><?php echo __('Please install and activate ', ATCAA_TEXT_DOMAIN); ?><a
                    href="https://wordpress.org/plugins/woocommerce/"
                    title="WooCommerce">WooCommerce</a><?php echo __(' before activating the plugin.', ATCAA_TEXT_DOMAIN); ?>
            </p>
        </div>
        <?php
    }
}