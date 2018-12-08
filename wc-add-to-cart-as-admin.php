<?php
/**
 * Plugin Name:     WC Add To Cart As Admin
 * Plugin URI:      https://github.com/tahireu/wc_add_to_cart_as_admin
 * Description:     Add products to your customer's cart from WP admin side.
 * Version:         1.0.1
 * Author:          Tahireu
 * Author URI:      https://github.com/tahireu/
 * License:         GPLv2 or later
 * License URI:     http://www.gnu.org/licenses/gpl-2.0.html
 */


/*
 * Prevent intruders from sneaking around
 * */
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );


/*
 * Variables
 * */
const ATCAA_TEXT_DOMAIN = "wc-add-to-cart-as-admin";


/*
 * Load ATCAA_Activator class before WooCommerce check
 * */
require plugin_dir_path( __FILE__ ) . 'includes/class-atcaa-activator.php';



/*
 * Check if WooCommerce is installed and active
 * */
include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
if ( is_plugin_active( 'woocommerce/woocommerce.php' ) ) {


    /*
     * Current plugin version - https://semver.org
     * This should be updated as new versions are released
     * */
    if( !defined( 'ATCAA_VERSION' ) ) {
        define( 'ATCAA_VERSION', '1.0.1' );
    }



    /*
     * Create database table on plugin activation
     * */
    function atcaa_create_table(){
        ATCAA_activator::atcaa_create_table();
    }

    register_activation_hook( __FILE__, 'atcaa_create_table' );




    /*
     * Do the work
     * */
    require plugin_dir_path( __FILE__ ) . 'functions.php';

    if ( is_admin() ) {
        require plugin_dir_path(__FILE__) . 'admin/class-atcaa-admin.php';
        ATCAA_admin::atcaa_on_load();
    }

    require plugin_dir_path(__FILE__) . 'public/class-atcaa-public.php';
    ATCAA_public::atcaa_on_load();


} else {

    /*
     * Abort and display info message
     * */
    ATCAA_activator::atcaa_abort();

}