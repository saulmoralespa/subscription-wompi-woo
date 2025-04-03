<?php

class Test_Validate_Transaction extends WP_UnitTestCase
{

    private WC_Order $order;

    protected function setUp(): void
    {
        parent::setUp();
        if (!class_exists('Subscription_Wompi')) {
            require_once dirname(__DIR__) . '/includes/class-subscription-wompi-sw.php';
        }

        $this->order = new WC_Order();
        $this->order->set_status('on-hold');
        $this->order->save();
    }

    public function test_validate_transaction_approved()
    {
        $this->order->payment_complete();
        Subscription_Wompi::validate_transaction('APPROVED', $this->order->get_id(), 'txn_12345', 1);
        $this->order->read_meta_data(true);
        $this->assertEquals('completed', $this->order->get_status(), 'La orden no se marcó como completada.');
    }

    public function test_validate_transaction_pending()
    {
        $this->order->update_status('pending');
        Subscription_Wompi::validate_transaction('PENDING', $this->order->get_id(), 'txn_12345', 1);
        $scheduled_event = wp_next_scheduled('subscription_wompi_scheduled_order', [$this->order->get_id(), 'txn_12345', 1]);
        $this->assertNotFalse($scheduled_event, 'El evento programado no fue creado correctamente.');
    }

    public function test_validate_transaction_failed()
    {
        $this->order->update_status('failed');
        Subscription_Wompi::validate_transaction('DECLINED', $this->order->get_id(), 'txn_12345', 1);
        $this->order->read_meta_data(true);
        $this->assertEquals('failed', $this->order->get_status(), 'La orden no se marcó como fallida.');
    }
}