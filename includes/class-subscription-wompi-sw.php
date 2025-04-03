<?php

if (!defined('ABSPATH')) {
    exit;
}

use Saulmoralespa\Wompi\Client;

class Subscription_Wompi
{
    private static ?Client $wompi = null;

    private static $settings = null;

    public static function test_connect(string $key_private, string $key_public, string $key_integrety): bool
    {
        try{
            $wompi = new Client($key_private, $key_public, $key_integrety);
            if(str_contains($key_private, 'test_')) $wompi->sandbox();
            $wompi->getAcceptanceTokens();

            $data = [
                "name" => "Pago de arriendo edificio Lombardía - AP 505",
                "description" => "Arriendo mensual", // Descripción del pago
                "single_use" => false,
                "collect_shipping" => false,
                "currency" => "COP",
                "amount_in_cents" => 500000
            ];
            $wompi->createPaymentLink($data);
        }catch(Exception $exception){
            subscription_wompi_sw()->log($exception->getMessage());
            return false;
        }

        return true;
    }

    public static function get_instance(): ?Client
    {
        $id = SUBSCRIPTION_WOMPI_SW_ID;

        if(isset(self::$settings) && isset(self::$wompi)) return self::$wompi;

        self::$settings = get_option("woocommerce_{$id}_settings", null);

        if(!isset(self::$settings)) return null;

        self::$settings = (object)self::$settings;

        if(self::$settings->enabled === 'no') return null;

        if(self::$settings->environment){
            self::$settings->key_private = self::$settings->sandbox_key_private;
            self::$settings->key_public = self::$settings->sandbox_key_public;
            self::$settings->key_integrety = self::$settings->sandbox_key_integrety;
        }

        self::$wompi = new Client(self::$settings->key_private, self::$settings->key_public, self::$settings->key_integrety);
        if(self::$settings->environment){
            self::$wompi->sandbox();
        }

        return self::$wompi;
    }

    public static function card_subscription(WC_Order $order): void
    {
        if (!self::get_instance()) return;

        $expire = $_POST['subscription-wompi-expiry'];
        $number = preg_replace('/\s+/', '', $_POST['subscription-wompi-number']);
        list($month, $year) = explode('/', $expire);
        $order_id = $order->get_id();

        $card_data = [
            "number" => $number,
            "exp_month"  => $month,
            "exp_year" => $year,
            "cvc" => $_POST['subscription-wompi-cvc'],
            "card_holder" => $_POST['subscription-wompi-name']
        ];
        $card_token = self::get_instance()->cardToken($card_data);
        $tokens = self::get_instance()->getAcceptanceTokens();

        $source_data = [
            "type" =>  "CARD",
            "token" =>  $card_token['data']['id'],
            "customer_email" => $order->get_billing_email(),
            "acceptance_token" => $tokens['data']['presigned_acceptance']['acceptance_token'],
            "accept_personal_auth" => $tokens['data']['presigned_personal_data_auth']['acceptance_token']
        ];

        $source = self::get_instance()->createSource($source_data);
        $payment_source_id = $source['data']['id'];
        $reference = "{$order->get_id()}-".time();

        $transaction = self::transaction($order, $reference, $payment_source_id);
        $transaction_id = $transaction['data']['id'];
        $status = $transaction['data']['status'];

        self::validate_transaction($status, $order_id, $transaction_id, $payment_source_id);

        add_user_meta( $order->get_user_id(), '_wompi_payment_source_id', $payment_source_id );

        $order->set_transaction_id($transaction_id);
        $order->save();
    }

    public static function scheduled_order(int $order_id, string $transaction_id, int $payment_source_id):void
    {
        try {
            $transaction = self::get_instance()->getTransaction($transaction_id);
            $transaction_id = $transaction['data']['id'];
            $status = $transaction['data']['status'];

            self::validate_transaction($status, $order_id, $transaction_id, $payment_source_id);

        } catch (\Exception $exception) {
            subscription_wompi_sw()->log($exception->getMessage());
        }
    }

    public static function validate_transaction(string $status, int $order_id, string $transaction_id, int $payment_source_id): void
    {
        $order = new WC_Order($order_id);

        switch ($status) {
            case 'APPROVED':
                $order->payment_complete();
                break;
            case 'PENDING':
                wp_schedule_single_event(time() + MINUTE_IN_SECONDS, 'subscription_wompi_scheduled_order', [$order_id, $transaction_id, $payment_source_id]);
                break;
            default:
                $order->update_status('failed');
        }
    }

    public static function transaction(WC_Order $order, string $reference, int $payment_source_id): array
    {
        $transaction_data = [
            "amount_in_cents" => $order->get_total() * 100,
            "currency" => $order->get_currency(),
            "customer_email" => $order->get_billing_email(),
            "payment_method" => [
                "installments" => 1
            ],
            "reference" => $reference,
            "payment_source_id" => $payment_source_id,
        ];

        return self::get_instance()->transaction($transaction_data);
    }
}