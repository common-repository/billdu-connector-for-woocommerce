<?php

/**
 * @package Billdu - Invoice Generator for WooCommerce
 * @author info@kodujeme.sk
 *
 * @wordpress-plugin
 * Plugin Name: Billdu - Invoice Generator for WooCommerce
 * Plugin URI: https://www.billdu.com/
 * Description:
 * Version: 1.02
 */

if (!function_exists('add_action')) {
    echo 'Hi there!  I\'m just a plugin, not much I can do when called directly.';
    exit;
}

define('MF_PLUGIN_URL', plugin_dir_url(__FILE__));

register_activation_hook(__FILE__, function () {
    update_option('MF_method_cod', true);
});

add_action('admin_enqueue_scripts', function () {
    wp_enqueue_script('MF-api-js', plugins_url('assets/main.js', __FILE__), array('jquery'));
    wp_enqueue_style('MF-api-css', plugins_url('assets/main.css', __FILE__));
});


require_once('includes/SettingPanel.php');
require_once('includes/Orders.php');
require_once('includes/OrderMetaBox.php');
require_once('api-client/vendor/autoload.php');
require_once('api-client/loader.php');

use MF\SettingPanel;
use MF\OrderMetaBox;
use MF\Orders;

$publicKey = get_option('MF_apiKey');
$secret = get_option('MF_apiSecret');

new Orders();
new SettingPanel();
new OrderMetaBox();
