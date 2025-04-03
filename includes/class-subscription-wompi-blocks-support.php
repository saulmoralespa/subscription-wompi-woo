<?php


use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

final class Subscription_Wompi_Payment_Blocks_Support extends AbstractPaymentMethodType
{
    private $gateway;

    protected $name = 'subscription_wompi_sw';

    public function initialize(): void
    {
        $this->settings = get_option( "woocommerce_{$this->name}_settings", array() );
        $this->gateway = new WC_Subscription_Wompi_SW();
    }

    public function is_active(): bool
    {
        return $this->gateway->is_available();
    }

    public function get_payment_method_script_handles(): array
    {
        $asset_path   = plugin_dir_path( __DIR__ ) . 'assets/build/index.asset.php';

        $version      = null;
        $dependencies = array();
        if( file_exists( $asset_path ) ) {
            $asset        = require $asset_path;
            $version      = isset( $asset[ 'version' ] ) ? $asset[ 'version' ] : $version;
            $dependencies = isset( $asset[ 'dependencies' ] ) ? $asset[ 'dependencies' ] : $dependencies;
        }

        wp_register_script(
            'wc-subscription-wompi-blocks-integration',
            plugin_dir_url( __DIR__ ) . 'assets/build/index.js',
            $dependencies,
            $version,
            true
        );

        return array( 'wc-subscription-wompi-blocks-integration' );

    }

    public function get_supported_features(): array
    {
        return array_filter( $this->gateway->supports, [ $this->gateway, 'supports' ] );
    }

    public function get_payment_method_data(): array
    {
        return array(
            'title'        => $this->get_setting( 'title' ),
            'description'  => $this->get_setting( 'description' ),
            'icon'         => plugin_dir_url( __DIR__ ) . 'assets/img/logo.png',
            'supports'  => $this->get_supported_features(),
        );
    }
}