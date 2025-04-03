<?php

if (!defined('ABSPATH')) {
    exit;
}

class WC_Subscription_Wompi_SW extends WC_Payment_Gateway
{
    public bool $isTest;

    public string $key_private;

    public string $key_public;

    public string $key_integrety;

    public function __construct()
    {
        $this->id = SUBSCRIPTION_WOMPI_SW_ID;
        $this->icon = subscription_wompi_sw()->plugin_url . 'assets/img/logo.png';
        $this->method_title = __('Suscripción con Wompi');
        $this->method_description = __('Suscripción a través de Wompi');
        $this->title = $this->get_option('title');
        $this->description  = $this->get_option( 'description' );
        $this->has_fields = true;
        $this->supports = [
            'products',
            'subscriptions',
            'subscription_suspension',
            'subscription_reactivation',
            'subscription_cancellation',
            'multiple_subscriptions',
            /*'add_payment_method',
            'tokenization',
            'refunds'*/
        ];

        $this->isTest = (bool)$this->get_option( 'environment' );

        if ($this->isTest){
            $this->key_private = $this->get_option('sandbox_key_private');
            $this->key_public = $this->get_option('sandbox_key_public');
            $this->key_integrety = $this->get_option('sandbox_key_integrety');
        }else{
            $this->key_private = $this->get_option('key_private');
            $this->key_public = $this->get_option('key_public');
            $this->key_integrety = $this->get_option('key_integrety');
        }

        $this->init_form_fields();
        $this->init_settings();
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
        add_action('woocommerce_subscription_status_cancelled', array($this, 'subscription_status_cancelled'));
        //add_action('woocommerce_scheduled_subscription_expiration', array($this, 'subscription_expiration'));
        add_action('woocommerce_scheduled_subscription_payment_'. $this->id , array($this, 'scheduled_subscription_payment'), 10, 2);
    }

    public function init_form_fields(): void
    {
        $this->form_fields = require( dirname( __FILE__ ) . '/admin/settings.php' );
    }

    public function needs_setup(): bool
    {
        return !$this->is_available();
    }

    public function is_available(): bool
    {
        if (!parent::is_available() || !$this->key_private || !$this->key_public || !$this->key_integrety) {
            return false;
        }

        if (!WC()->cart || WC()->cart->is_empty()) {
            return false;
        }

        foreach (WC()->cart->get_cart() as $cart_item) {
            if (!WC_Subscriptions_Product::is_subscription($cart_item['data'])) {
                return false;
            }
        }

        return true;
    }
    public function validate_password_field($key, $value):string
    {
        $index_key_public =  $key === 'sandbox_key_private' ? 'sandbox_key_public' : 'key_public';
        $index_key_integrety =  $key === 'sandbox_key_private' ? 'sandbox_key_integrety' : 'key_integrety';
        $key_public = $_POST["woocommerce_{$this->id}_{$index_key_public}"] ?? null;
        $key_integrety = $_POST["woocommerce_{$this->id}_{$index_key_integrety}"] ?? null;
        $enabled = $_POST["woocommerce_{$this->id}_enabled"] ?? false;

        $validation_rules = [
            'sandbox_key_private' => [
                'message' => 'La llave privada debe contener "prv_test_"',
                'key' => 'prv_test_'
            ],
            'key_private' => [
                'message' => 'La llave privada debe contener "prv_prod_"',
                'key' => 'prv_prod_'
            ]
        ];

        if(isset($validation_rules[$key]) &&
            $value &&
            !str_contains($value, $validation_rules[$key]['key'])) {
            WC_Admin_Settings::add_error($validation_rules[$key]['message']);
        }

        if($enabled &&
            $value &&
            $key_public &&
            $key_integrety &&
            !Subscription_Wompi::test_connect($value, $key_public, $key_integrety)) {

            WC_Admin_Settings::add_error("Credenciales inválidas");
            $value = '';
        }

        return $value;
    }

    public function validate_text_field($key, $value):string
    {

        $validation_rules = [
            'sandbox_key_public' => [
                'message' => 'La llave pública debe contener "pub_test_"',
                'key' => 'pub_test_'
            ],
            'key_public' => [
                'message' => 'La llave pública debe contener "pub_prod_"',
                'key' => 'pub_prod_'
            ],
            'sandbox_key_integrety' => [
                'message' => 'La Llave de integridad debe contener "test_integrity_"',
                'key' => 'test_integrity_'
            ],
            'key_integrety' => [
                'message' => 'La Llave de integridad debe contener "prod_integrity_"',
                'key' => 'prod_integrity_'
            ]
        ];

        if(isset($validation_rules[$key]) &&
            $value &&
            !str_contains($value, $validation_rules[$key]['key'])) {
            WC_Admin_Settings::add_error($validation_rules[$key]['message']);
        }

        return $value;
    }

    public function payment_fields(): void
    {
        if ( $description = $this->get_description() ) {
            echo wp_kses_post( wpautop( wptexturize( $description ) ) );
        }
        ?>
        <div id="card-subscription-wompi">
            <div class='card-wrapper'></div>
            <div id="form-wompi">
                <input placeholder="<?php echo __('Número de tarjeta', 'subscription-wompi'); ?>" type="tel" name="subscription-wompi-number" id="subscription-wompi-number" required="" class="form-control">
                <input placeholder="<?php echo __('Titular de la tarjeta'); ?>" type="text" name="subscription-wompi-name" id="subscription-wompi-name" required="" class="form-control">
                <input type="hidden" name="subscription-wompi-type" id="subscription-wompi-type">
                <input placeholder="MM/YY" type="tel" name="subscription-wompi-expiry" id="subscription-wompi-expiry" required="" class="form-control" >
                <input placeholder="123" type="password" name="subscription-wompi-cvc" id="subscription-wompi-cvc" required="" class="form-control" maxlength="4">
            </div>
            <div class="error-subscription-wompi" style="display: none">
                <span class="message"></span>
            </div>
        </div>
        <?php

    }
    public function process_payment($order_id): array
    {
        $order = new WC_Order($order_id);

        $isFailed = true;

        try{
            Subscription_Wompi::card_subscription($order);
            $isFailed = false;
        }catch (\Exception $exception){
            subscription_wompi_sw()->log($exception->getMessage());
            wc_add_notice('Error al procesar el pago', 'error' );
        }

        return array(
            'result' => $isFailed ? 'fail' : 'success',
            'redirect' => $isFailed ? '' : $order->get_checkout_order_received_url()
        );

    }

    //TODO: Refactor only use for Bancolombia
    public function subscription_status_cancelled(WC_Subscription $subscription): void
    {
        /** @var WC_Order $order */
        $order = $subscription->get_parent();
        $payment_source_id = (int)get_user_meta($order->get_user_id(), '_wompi_payment_source_id', true);

        $wompi = Subscription_Wompi::get_instance();
        if(!$wompi || !$payment_source_id) return;

        try{
            $wompi->cancelSubscription($payment_source_id);
        }catch (\Exception $exception){
            subscription_wompi_sw()->log($exception->getMessage());
        }
    }

    public function scheduled_subscription_payment($amount_to_charge, WC_Order $order): void
    {
        $payment_source_id = (int)get_user_meta($order->get_user_id(), '_wompi_payment_source_id', true);

        $wompi = Subscription_Wompi::get_instance();
        if(!$wompi || !$payment_source_id) return;

        subscription_wompi_sw()->log(__FUNCTION__);

        try{
            $reference = "{$order->get_id()}-".time();
            $transaction = Subscription_Wompi::transaction($order, $reference, $payment_source_id);
            $transaction_id = $transaction['data']['id'];
            $status = $transaction['data']['status'];
            Subscription_Wompi::validate_transaction($status, $order->get_id(), $transaction_id, $payment_source_id);
        }catch (\Exception $exception){
            subscription_wompi_sw()->log($exception->getMessage());
        }
    }
}