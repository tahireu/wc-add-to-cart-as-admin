<?php
/**
 * Prevent intruders from sneaking around
 */
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );


/**
 * WC ATCAA Admin class
 */
class ATCAA_admin
{

    const FORM_ID = "atcaa-prepare-product-form";



    public static function atcaa_on_load()
    {
        add_action('plugins_loaded', array(__CLASS__, 'init'));
    }



    public static function init()
    {
        /* Load scripts */
        add_action('admin_enqueue_scripts', array(__CLASS__, 'atcaa_load_scripts'));

        /* Display plugin meta box */
        add_action('add_meta_boxes', array(__CLASS__, 'atcaa_add_meta_box'));

        /* Render actual form outside the main form (in footer), to prevent form nesting */
        add_filter('admin_footer', array(__CLASS__, 'atcaa_render_prepare_product_form'));

        /* AJAX suggest users */
        add_action('wp_ajax_get_listing_names', array(__CLASS__, 'atcaa_suggest_users'));

        /* Add product to database table from which items are added to cart on user login */
        add_action('wp_ajax_prepare_product', array(__CLASS__, 'atcaa_prepare_for_cart'));

        /* ATCAA Overview admin page */
        add_action('admin_menu', array(__CLASS__, 'atcaa_overview_page'));

        /* AJAX delete single item in ATCAA Overview page */
        add_action('wp_ajax_delete_item', array(__CLASS__, 'atcaa_delete_single_item'));

        /* AJAX clear all items for selected user in ATCAA Overview page */
        add_action('wp_ajax_clear_user_items', array(__CLASS__, 'atcaa_delete_all_items_for_user'));

    }



    /*
     * Load class scripts
     */
    public static function atcaa_load_scripts()
    {
        /* CSS */
        wp_enqueue_style('autocomplete-css', plugins_url('/vendor/jquery-autocomplete/css/jquery.auto-complete.css', __FILE__));
        wp_enqueue_style('atcaa-admin-css', plugins_url('/css/atcaa-admin.css', __FILE__));

        /* JS */
        wp_enqueue_script('autocomplete', plugins_url('/vendor/jquery-autocomplete/js/jquery.auto-complete.js', __FILE__), array('jquery'));
        wp_enqueue_script('atcaa-admin-js', plugins_url('/js/atcaa-admin.js', __FILE__), array('jquery', 'autocomplete'));

        /* AJAX */
        wp_localize_script('atcaa-admin-js', 'atcaa_admin_ajax_object', array('ajax_url' => admin_url('admin-ajax.php')));

    }



    /*
     * Render forms in footer, outside of WP admin main form, to prevent form nesting
     * These forms fields are connected with their forms with "form" property - https://www.w3schools.com/tags/att_form.asp
     * */
    public static function atcaa_render_prepare_product_form()
    {
        $current_screen = get_current_screen();

        if ($current_screen->post_type === "product") {
            echo "<form type='hidden' method='POST' id=" . self::FORM_ID . "></form>";
        }
    }



    /*
     * Add ATCAA meta box
     * */
    public static function atcaa_add_meta_box()
    {
        add_meta_box(
            'atcaa_meta_box',
            __('WC Add To Cart As Admin', ATCAA_TEXT_DOMAIN),
            [__CLASS__, 'atcaa_meta_box_html'],
            'product'
        );
    }



    /*
     * HTML content for ATCAA meta box
     * */
    public static function atcaa_meta_box_html()
    {
        echo "
    <div class='atcaa-meta-box-main-holder'>

        <!-- Description -->
        <div class='atcaa-title-and-description'>
            <h1>" . __('Add this product to your customer\'s cart', ATCAA_TEXT_DOMAIN) . "</h1>
            <span class='howto'>" . __('When selected user logs in, products added here will appear in his shopping cart. 
            This way, you can prepare customer\'s cart content for him - fill it with items you think that suits him best. ', ATCAA_TEXT_DOMAIN) . "</span>
        </div>

        <!-- ATCAA form fields -->
        <div class='atcaa-form'>
            <input  type='text' name='user_name' id='user_name' placeholder='" . __('Start typing username...', ATCAA_TEXT_DOMAIN) . "' form=" . self::FORM_ID . " />
            <input  type='number' name='quantity' id='quantity' placeholder='" . __('Quantity', ATCAA_TEXT_DOMAIN) . "' form=" . self::FORM_ID . " />
            <select type='text' name='variations' title='Variations' form=" . self::FORM_ID . "> " . self::render_dropdown_options() . "</select>" . "
            <input  type='hidden' name='product_id' value=" . wc_get_product()->get_id() . " form=" . self::FORM_ID . " />
            <!-- Value in field below must correspond with wp_ajax_prepare_product action (without wp_ajax_ prefix) -->
            <input  type='hidden' name='action' value='prepare_product' form=" . self::FORM_ID . " />
            <button type='submit' name='submit' class='button atcaa-prepare-product-submit button-primary' form=" . self::FORM_ID . ">" . __('Add to customer\'s cart', ATCAA_TEXT_DOMAIN) . "</button>
        </div>
        
        <!-- AJAX Feedback container -->
        <div id='atcaa-form-feedback' class='atcaa-form-feedback'></div>
       
        <!-- Loading style -->
        <div class='atcaa-loading-overlay'></div> 
        <div class='atcaa-loader-image'>
            <div class='atcaa-loader-image-inner'></div>
        </div>
        
    </div>";
    }



    /*
     * Dropdown options for ATCAA form
     * */
    public static function render_dropdown_options()
    {
        $handle = new WC_Product_Variable($_GET['post']);
        $variations = $handle->get_children();

        $variations_placeholder = $variations ? __('Select variations', ATCAA_TEXT_DOMAIN) : __('Select variations (No variations available)', ATCAA_TEXT_DOMAIN);
        $output = '<option  value="">' . $variations_placeholder . '</option>';

        foreach ($variations as $value) {
            $single_variation = new WC_Product_Variation($value);
            $output .= '<option value="' . $value . '">' . implode(" / ", $single_variation->get_variation_attributes()) . '-' . get_woocommerce_currency_symbol() . $single_variation->get_price() . '</option>';
        }

        return $output;
    }



    /*
     * AJAX Suggest users after admin start typing customer's name
     * */
    public static function atcaa_suggest_users()
    {
        global $wpdb;

        /* Get names */
        $name = $wpdb->esc_like(stripslashes($_POST['user_name'])) . '%'; // escape for use in LIKE statement
        $query = "SELECT * FROM {$wpdb->prefix}users WHERE display_name LIKE %s ORDER BY user_login ASC";

        $query = $wpdb->prepare($query, $name);

        $results = $wpdb->get_results($query);

        $users = array();
        foreach ($results as $result)
            $users[] = addslashes($result->display_name);

        /* Encode into JSON format and output */
        echo json_encode($users);

        wp_die();
    }



    /*
     * AJAX Add items to database table so they are ready to be added to cart when user logs in
     * */
    public static function atcaa_prepare_for_cart()
    {

        global $wpdb;
        $error_string = $price = $product_level_stock = $stock_status = $incompatible_product_type = '';

        /* Required fields validation */
        $empty_fields = array();
        $required_fields = array('quantity', 'user_name');
        $empty_fields = array_merge($empty_fields, atcaa_check_required_fields($required_fields));
        $empty_fields = implode(", ", $empty_fields);


        /* Length validation */
        $too_long_fields = array();
        $fields_max_lengths = array('user_name' => 50, 'quantity' => 10);
        $too_long_fields = array_merge($too_long_fields, atcaa_check_field_length($fields_max_lengths));
        $too_long_fields = implode(", ", $too_long_fields);


        /* Store AJAX data into PHP variables */
        $quantity = atcaa_prepare($_POST["quantity"]);
        $product_id = atcaa_prepare($_POST["product_id"]);
        $user_name = atcaa_prepare($_POST["user_name"]);
        $variation_id = atcaa_prepare($_POST["variations"]);
        if ($variation_id !== '') {
            $product_id = $variation_id;
        }


        /* Get product info relevant for input validation */
        $product_level_stock = get_post_meta($product_id, '_manage_stock', true);
        $stock_status = get_post_meta($product_id, '_stock_status', true);
        $price = get_post_meta($product_id, '_price', true);
        $_product = wc_get_product( $product_id );
        if( $_product->is_type( 'grouped') || $_product->is_type( 'external') ) {
            $incompatible_product_type = true;
        }


        /* Start building $error_string */
        if ($_product->is_type( 'grouped')) {
            $error_string .= "<div class='atcaa-message atcaa-message-error'>" . __('Sorry, this plugin is not compatible with ', ATCAA_TEXT_DOMAIN) . "<b>" . __('grouped ', ATCAA_TEXT_DOMAIN) . "</b>" . __('product type.', ATCAA_TEXT_DOMAIN) . "</div>";
        }

        if ($_product->is_type( 'external')) {
            $error_string .= "<div class='atcaa-message atcaa-message-error'>" . __('You cannot add ', ATCAA_TEXT_DOMAIN) . "<b>" . __('external ', ATCAA_TEXT_DOMAIN) . "</b>" . __('product to user\'s cart.', ATCAA_TEXT_DOMAIN) . "</div>";
        }

        if ($product_level_stock !== 'no') {
            $error_string .= "<div class='atcaa-message atcaa-message-error'>" . __('Sorry, this plugin is not compatible with', ATCAA_TEXT_DOMAIN) . "<b>" . __(' stock management at product level', ATCAA_TEXT_DOMAIN) . "</b>" . __(' WooCommerce option.', ATCAA_TEXT_DOMAIN) . "</div>";
        }


        /* Don't even start checking anything if 'product level stock' option is turned on, or if product type is incompatible with this plugin */
        if ($product_level_stock == 'no' && $incompatible_product_type !== true) {

            if ($empty_fields !== "") {
                $error_string .= "<div class='atcaa-message atcaa-message-error'>" . __('Field(s) ', ATCAA_TEXT_DOMAIN) . "<b>" . $empty_fields . "</b>" . __(' is/are required. Please try again.', ATCAA_TEXT_DOMAIN) . "</div>";
            }

            if ($too_long_fields !== "") {
                $error_string .= "<div class='atcaa-message atcaa-message-error'>" . __('Field(s) ', ATCAA_TEXT_DOMAIN) . "<b>" . $too_long_fields . "</b>" . __(' is/are too long. Please try again.', ATCAA_TEXT_DOMAIN) . "</div>";
            }

            if ($price == '') {
                $error_string .= "<div class='atcaa-message atcaa-message-error'><b>" . __(' Items without price ', ATCAA_TEXT_DOMAIN) . "</b>" . __('cannot be added to user cart.', ATCAA_TEXT_DOMAIN) . "</div>";
            }

            if($stock_status !== 'instock') {
                $error_string .= "<div class='atcaa-message atcaa-message-error'>" . __('Item is currently', ATCAA_TEXT_DOMAIN) . "<b>" . __(' out of stock.', ATCAA_TEXT_DOMAIN) . "</b></div>";
            }

            $query = "SELECT ID FROM {$wpdb->prefix}users WHERE display_name = '$user_name' LIMIT 1";
            $user_id = $wpdb->get_results($query);


            if (count($user_id) == 0 && $user_name !== '') {
                $error_string .=  "<div class='atcaa-message atcaa-message-error'>" . __('User ', ATCAA_TEXT_DOMAIN) . "<b>" . $user_name . "</b>" . __(' does not exists. Please try again.', ATCAA_TEXT_DOMAIN) . "</div>";
            }


            /* Execute if $error_string is empty */
            if ($error_string == '') {
                $query = "INSERT INTO {$wpdb->prefix}atcaa_prepared_items (";
                $query .= "user_id, quantity, product_id, variation_id";
                $query .= ") values ('";
                $query .= $user_id[0]->ID . "', '";
                $query .= $quantity . "', '";
                $query .= $product_id . "', '";
                $query .= $variation_id . "')";

                $wpdb->get_results($query);

                if ($wpdb->last_error) {
                    $error_string .= "<div class='atcaa-message atcaa-message-error'>" . __('Could not connect: ', ATCAA_TEXT_DOMAIN) . $wpdb->last_error . "</div>";
                    echo $error_string;
                } else {
                    echo "<div class='atcaa-message atcaa-message-success'>" . $user_name . __(' cart will be updated on login. ', ATCAA_TEXT_DOMAIN) . "<a href='" . admin_url( 'admin.php?page=wc-add-to-cart-as-admin-overview') . "' target='_blank'>" . __('View all prepared items.', ATCAA_TEXT_DOMAIN) . "</a></div>";
                }

            } else {
                echo $error_string;
            }

        } else {
            echo $error_string;
        }

        wp_die();
    }



    /*
     * Add ATCAA Overview admin page
     * */
    public static function atcaa_overview_page() {
        add_submenu_page(
            'woocommerce',
            __('WC Add To Cart As Admin Overview', ATCAA_TEXT_DOMAIN),
            __('WC Add To Cart As Admin Overview', ATCAA_TEXT_DOMAIN),
            'manage_options',
            'wc-add-to-cart-as-admin-overview', // search whole plugin code for usage before eventual edit
            array( __CLASS__, 'atcaa_overview_page_callback' )
        );
    }



    /*
     * Render ATCAA Overview page content
     * */
    public static function atcaa_overview_page_callback() {
        global $wpdb;

        echo "
        <div class='wrap'>
        
            <h2>" . __('WC Add To Cart As Admin Overview', ATCAA_TEXT_DOMAIN) . "</h2>
            <div class='atcaa-overview-holder'>
                <div class='atcaa-overview-info howto'>
                    <span>" . __('These items will be added to user\'s cart when he logs in. Until then, you can remove items added by mistake.', ATCAA_TEXT_DOMAIN) . "
                    <br />
                    " . __('After user place order, items for that user will be automatically removed from this page.', ATCAA_TEXT_DOMAIN) . "
                    </span>
                </div>
                <div class='atcaa-overview-toggle-visibility'>
                    <button id='expand-all' class='button-primary button'>" . __('Expand All', ATCAA_TEXT_DOMAIN) . "</button>
                    <button id='collapse-all' class='button-primary button'>" . __('Collapse All', ATCAA_TEXT_DOMAIN) . "</button>
                </div>
            </div>
            <div id='atcaa-overview-feedback'></div>";


            $query = "SELECT DISTINCT user_id FROM {$wpdb->prefix}atcaa_prepared_items";
            $user_ids = $wpdb->get_results($query);


            if ($user_ids) {
            foreach ($user_ids as $user_id) {

                $id = $user_id->user_id;
                $user = get_user_by('id', $id);
                $row_number = 0;


                $query = "SELECT  id, 
                                  product_id, 
                                  variation_id,
                                  imported_to_cart,
                                  SUM(quantity) qntty 
                          FROM {$wpdb->prefix}atcaa_prepared_items 
                          WHERE user_id = $id 
                          GROUP BY id";

                $data_per_id = $wpdb->get_results($query);


                echo "
                <table id='atcaa-table-user-id-" . $id . "' class='atcaa-overview-table widefat fixed striped'>
                
                    <caption>
                        <span>" . $user->display_name . "</span>
                        <button id='atcaa-clear-all' class='atcaa-clear-all' value = " . $id . ">" . __('Clear All', ATCAA_TEXT_DOMAIN) . "</button>
                    </caption>
                    
                    <thead>
                        <tr>
                            <td class='atcaa-table-row-number'>" . __('#', ATCAA_TEXT_DOMAIN) . " </td>
                            <td class='atcaa-delete'></td>
                            <td>" . __('Product Name', ATCAA_TEXT_DOMAIN) . " </td>
                            <td>" . __('Quantity', ATCAA_TEXT_DOMAIN) . " </td>
                            <td>" . __('Price', ATCAA_TEXT_DOMAIN) . " </td>
                        </tr>
                    </thead>
                    
                    <tbody id='the-list'>";
                        foreach ($data_per_id as $data) {

                            $row_number++;

                            if ($data->variation_id !== '0') {
                                $id = wc_get_product($data->variation_id);
                            } else {
                                $id = wc_get_product($data->product_id);
                            }

                            echo "
                                <tr class='entry-id-" . $data->id . "'>
                                    <td>" . $row_number . "</td>";
                            if ($data->imported_to_cart == 0) {
                            echo "
                                    <td class='atcaa-delete'><button id='atcaa-entry-delete-button' class='button-primary button' value = " . $data->id . ">" . __('Delete', ATCAA_TEXT_DOMAIN) . "</button></td>";
                            } else {
                                echo "
                                    <td class='atcaa-already-in-cart'>" . __('Already added to cart', ATCAA_TEXT_DOMAIN) . " </td>";
                            }
                            echo "
                                    <td>" . $id->get_name() . "</td>
                                    <td>" . $data->qntty . "</td>
                                    <td>" . get_woocommerce_currency_symbol() . $id->get_price()*$data->qntty . "</td>
                                </tr>";
                        }
                echo "</tbody>

                </table>";

                }
            } else {
                echo "<br /><br /><h3>" . __('There is nothing here. Check ', ATCAA_TEXT_DOMAIN) . "
                <a href='" . admin_url( 'edit.php?post_type=shop_order') . "' target='_blank'><b>" . __('Orders page', ATCAA_TEXT_DOMAIN) . "</b></a>" .
                    __(' to see if some of yours previously added products were processed, or add new products to users carts.', ATCAA_TEXT_DOMAIN) . "</h3>";
            }
        echo "</div>";

    }



    /*
     * Delete from ATCAA Overview page
     * */
    public static function atcaa_delete_prepared($table_column, $post_value) {

        global $wpdb;

        $query = "DELETE FROM {$wpdb->prefix}atcaa_prepared_items WHERE $table_column = $post_value";
        $wpdb->get_results($query);

        wp_die();
    }



    /*
    * Delete single item from ATCAA Overview page
    * */
    public static function atcaa_delete_single_item() {
        self::atcaa_delete_prepared('id', $_POST['item']);
    }



    /*
    * Delete all items per user from ATCAA Overview page
    * */
    public static function atcaa_delete_all_items_for_user() {
        self::atcaa_delete_prepared('user_id', $_POST['user_id']);
    }

}