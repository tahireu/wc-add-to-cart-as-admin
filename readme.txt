=== WC Add To Cart As Admin ===
Contributors: tahireu
Tags: woocommerce, webshop, shop, e-shop, web-shop, online-store, store
Requires at least: 3.1.0
Tested up to: 5.0
Stable tag: 1.0.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

This WooCommerce/WordPress plugin will give you ability to add products to your customer's cart from WP admin side.

== Description ==

This WooCommerce/WordPress plugin will give you ability to add products to your customer's cart from WP admin side.

== Installation ==

= From your WordPress dashboard =

1. Visit 'Plugins > Add New'
2. Search for 'WC Add To Cart As Admin'
3. When you find it, click 'install', and after that, click 'activate'
4. On each WooCommerce Product page you'll find WC Add To Cart As Admin meta box which will allow you to select user and add that item to it's cart
5. On 'WooCommerce > WC Add To Cart As Admin Overview' page, you will be able to see the status of prepared items pre user and delete items added by mistake

= From WordPress.org =

1. Download 'WC Add To Cart As Admin'.
2. Upload the 'WC Add To Cart As Admin' directory to your '/wp-content/plugins/' directory, using your favorite method (ftp, sftp, scp, etc...)
3. When you upload it, click 'install', and after that, click 'activate'
4. On each WooCommerce Product page you'll find WC Add To Cart As Admin meta box which will allow you to select user and add that item to it's cart
5. On 'WooCommerce > WC Add To Cart As Admin Overview' page, you will be able to see the status of prepared items pre user and delete items added by mistake

== Screenshots ==

1. WC Add To Cart As Admin meta box on the bottom of each WooCommerce Product page in WP admin area
2. WC Add To Cart As Admin Overview page

== Changelog ==

= 1.0.1 =
* Removed unnecessary comments
* Fixed typos
* Updated info messages in ATCAA meta box to be more accurate, understandable and self-explainable
* Updated info messages behavior in ATCAA meta box to make more logic
* Updated and improved howto explanations on ATCAA Overview page and in ATCAA Meta Box
* Updated readme.txt description, stable tag, tested up to, known issues and plans for the future.
* Updated screenshots relevant to these changes

== Upgrade Notice ==

= 1.0.1 =
Main advantages of 1.0.1 are better error handling and improved info messages behavior and explanations when submitting data in ATCAA meta box.
Also, this patch will improve howto explanations on both 'Product > ATCAA meta box' and 'ATCAA Overview' pages.

== Known Issues ==

* Plugin is not compatible with 'Stock management at product level' WooCommerce option
* Plugin is not compatible with 'Grouped' product type
* 'Delete' and 'Clear All' buttons on Overview page are not working in responsive/mobile mode in Firefox due to Firefox bug: https://bugzilla.mozilla.org/show_bug.cgi?id=1273997

== Plans For The Future ==

* Make it compatible with 'Stock management at product level' WooCommerce option
* Make it compatible with 'Grouped' products
* Add pagination and search field to WC Add To Cart As Admin Overview page
* Add languages support
* Make it to work even if AJAX fail
* Add 'dismiss' option to info messages

If you want some of the functionality mentioned above to be implemented, or you have your own suggestions, please contact me at https://github.com/tahireu or tahiri.damir[at]gmail.com