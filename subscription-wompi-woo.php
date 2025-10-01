<?php
/**
 * Plugin Name: Subscription Wompi WooCommerce
 * Description: Integración de suscripciones con Wompi para WooCommerce.
 * Version: 0.0.2
 * Author: Saúl Morales Pacheco
 * Author URI: https://saulmoralespa.com
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 * WC tested up to: 10.1
 * WC requires at least: 9.0
 * Requires at least: 6.0
 * Tested up to: 6.8
 * Requires PHP: 8.1
 * Requires Plugins: woocommerce
 */

if (!defined('ABSPATH')) {
    exit;
}

if(!defined('SUBSCRIPTION_WOMPI_SW_VERSION')){
    define('SUBSCRIPTION_WOMPI_SW_VERSION', '0.0.1');
}

if(!defined('SUBSCRIPTION_WOMPI_SW_ID')){
    define('SUBSCRIPTION_WOMPI_SW_ID', 'subscription_wompi_sw');
}

add_action( 'plugins_loaded', 'subscription_wompi_sw_init');
add_action(
    'before_woocommerce_init',
    function () {
        if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
                    'custom_order_tables',
                    __FILE__
            );
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
                'cart_checkout_blocks',
                __FILE__,
                false
            );
        }
    }
);

function subscription_wompi_sw_init():void
{
    if(!subscription_wompi_sw_requirements()) return;

    subscription_wompi_sw()->run_wompi();
}

function subscription_wompi_sw_notices($notice): void
{
    ?>
    <div class="error notice">
        <p><?php echo $notice; ?></p>
    </div>
    <?php
}

function subscription_wompi_sw_requirements():bool
{
    if ( !version_compare(PHP_VERSION, '8.1.0', '>=') ) {
        if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
            add_action(
                'admin_notices',
                function() {
                    subscription_wompi_sw_notices( 'Subscription Wompi WooCommerce: Requiere la versión de php >= 8.1');
                }
            );
        }
        return false;
    }

    if (!class_exists('WC_Subscriptions')) {
        if (is_admin() && !defined('DOING_AJAX')) {
            $url_docs = 'https://shop.saulmoralespa.com/subscription-wompi-woocommerce';

            $notice = sprintf(
                __('El plugin <strong>Subscription Wompi WooCommerce</strong> requiere que el plugin <strong>WooCommerce Subscriptions</strong> esté instalado y activo. %s.', 'woocommerce'),
                '<a target="_blank" href="' . esc_url($url_docs) . '" style="font-weight:bold;">' . __('Consulta la documentación de ayuda aquí', 'woocommerce') . '</a>'
            );

            add_action(
                'admin_notices',
                function () use ($notice) {
                    subscription_wompi_sw_notices($notice);
                }
            );
        }

        return false;
    }

    $shop_currency = get_option('woocommerce_currency');

    if ($shop_currency != 'COP') {
        if (is_admin() && !defined('DOING_AJAX')) {
            $currency_notice = sprintf(
                __('El plugin <strong>Subscription Wompi WooCommerce</strong> requiere que la moneda de la tienda sea <strong>COP</strong>. Actualmente, está configurada en una moneda no compatible. %s.', 'woocommerce'),
                '<a href="' . esc_url(admin_url('admin.php?page=wc-settings&tab=general#s2id_woocommerce_currency')) . '" style="font-weight:bold;">' . __('Haga clic aquí para cambiar la configuración', 'woocommerce') . '</a>'
            );

            add_action(
                'admin_notices',
                function () use ($currency_notice) {
                    subscription_wompi_sw_notices($currency_notice);
                }
            );
        }
        return false;
    }


    return true;
}

function subscription_wompi_sw() {
    static $plugin;
    if (!isset($plugin)){
        require_once('includes/class-subscription-wompi-sw-plugin.php');
        $plugin = new Subscription_Wompi_SW_Plugin(__FILE__, SUBSCRIPTION_WOMPI_SW_VERSION);
    }
    return $plugin;
}