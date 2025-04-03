<?php

if (!defined('ABSPATH')) {
    exit;
}

class Subscription_Wompi_SW_Plugin
{
    /**
     * Absolute plugin path.
     *
     * @var string
     */
    public string $plugin_path;
    /**
     * Absolute plugin URL.
     *
     * @var string
     */
    public string $plugin_url;
    /**
     * assets plugin.
     *
     * @var string
     */
    public string $assets;
    /**
     * Absolute path to plugin includes dir.
     *
     * @var string
     */
    public string $includes_path;
    /**
     * Absolute path to plugin lib dir
     *
     * @var string
     */
    public string $lib_path;
    /**
     * @var WC_Logger
     */
    public WC_Logger $logger;
    /**
     * @var bool
     */
    private bool $bootstrapped = false;

    public function __construct(
        protected $file,
        protected $version
    )
    {
        $this->plugin_path = trailingslashit(plugin_dir_path($this->file));
        $this->plugin_url = trailingslashit(plugin_dir_url($this->file));
        $this->assets = $this->plugin_url . trailingslashit('assets');
        $this->includes_path = $this->plugin_path . trailingslashit('includes');
        $this->lib_path = $this->plugin_path . trailingslashit('lib');
        $this->logger = new WC_Logger();
    }

    public function run_wompi(): void
    {
        try {
            if ($this->bootstrapped) {
                throw new Exception('Subscription Wompi WooCommerce can only be called once');
            }
            $this->run();
            $this->bootstrapped = true;
        } catch (Exception $e) {
            if (is_admin() && !defined('DOING_AJAX')) {
                add_action('admin_notices', function () use ($e) {
                    subscription_wompi_sw_notices($e->getMessage());
                });
            }
        }
    }

    private function run(): void
    {

        if (!class_exists('\Saulmoralespa\Wompi\Client')){
            require_once($this->lib_path . 'vendor/autoload.php');
        }

        if (!class_exists('WC_Subscription_Wompi_SW')) {
            require_once($this->includes_path . 'class-gateway-subscription-wompi-sw.php');
            add_filter( 'woocommerce_payment_gateways', array($this, 'add_gateway'));
        }

        if (!class_exists('Subscription_Wompi')) {
            require_once($this->includes_path . 'class-subscription-wompi-sw.php');
        }

        require_once($this->includes_path . 'class-subscription-wompi-blocks-support.php');

        require_once($this->lib_path . 'plugin-update-checker/plugin-update-checker.php');

        $myUpdateChecker = YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
            'https://github.com/saulmoralespa/subscription-wompi-woo',
            $this->file
        );

        $myUpdateChecker->setBranch('main');
        $myUpdateChecker->getVcsApi()->enableReleaseAssets();

        add_filter('plugin_action_links_' . plugin_basename($this->file), array($this, 'plugin_action_links'));

        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
        add_action( 'subscription_wompi_scheduled_order', array('Subscription_Wompi', 'scheduled_order'), 10, 3);
        //add_action( 'woocommerce_blocks_loaded', array($this, 'register_wc_blocks') );
    }


    public function add_gateway(array $methods): array
    {
        $methods[] = 'WC_Subscription_Wompi_SW';
        return $methods;
    }

    public function plugin_action_links(array $links): array
    {
        $id = SUBSCRIPTION_WOMPI_SW_ID;
        $links[] = '<a href="' . admin_url("admin.php?page=wc-settings&tab=checkout&section=$id") . '">' . 'Configuraciones' . '</a>';
        $links[] = '<a target="_blank" href="https://shop.saulmoralespa.com/subscription-wompi-woocommerce/">' . 'Documentación' . '</a>';
        return $links;
    }

    public function enqueue_scripts(): void
    {
        if(is_checkout()) {
            wp_enqueue_script( 'subscription-wompi-card', $this->plugin_url . 'assets/js/card.js', array( 'jquery' ), $this->version, true );
            wp_enqueue_script( 'subscription-wompi', $this->plugin_url . 'assets/js/subscription-wompi.js', array( 'jquery', 'subscription-wompi-card' ), $this->version, true );
            wp_localize_script( 'subscription-wompi', 'subscription_wompi', array(
                'msgNoCard' => __('No se acepta el tipo de tarjeta'),
                'msgEmptyInputs' => __('Introduzca los datos de la tarjeta'),
                'msgReturn' => __('Redirecting to verify status...'),
                'msgNoCardValidate' => __('Número de tarjeta no válido'),
                'msgValidateDate' => __('Fecha de caducidad de la tarjeta no válida')
            ));
            wp_enqueue_style( 'frontend-subscription-wompi', $this->plugin_url . 'assets/css/subscription-wompi.css', array(), $this->version, null);
        }
    }

    public function register_wc_blocks(): void
    {
        add_action(
            'woocommerce_blocks_payment_method_type_registration',
            function( Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry ) {
                $payment_method_registry->register( new Subscription_Wompi_Payment_Blocks_Support );
            }
        );
    }

    public function log($message): void
    {
        $id = SUBSCRIPTION_WOMPI_SW_ID;
        $message = (is_array($message) || is_object($message)) ? print_r($message, true) : $message;
        $this->logger->add($id, $message);
    }
}