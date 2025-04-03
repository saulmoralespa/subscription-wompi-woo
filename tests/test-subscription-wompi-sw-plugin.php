<?php

class Test_Subscription_Wompi_SW_Plugin extends WP_UnitTestCase
{

    private Subscription_Wompi_SW_Plugin $plugin;

    protected function setUp(): void
    {
        parent::setUp();
        if (!class_exists('Subscription_Wompi_SW_Plugin')) {
            require_once dirname(__DIR__) . '/includes/class-subscription-wompi-sw-plugin.php';
        }

        $this->plugin = new Subscription_Wompi_SW_Plugin(__FILE__, '0.0.1');
    }

    public function test_plugin_initialization()
    {
        $this->assertInstanceOf(Subscription_Wompi_SW_Plugin::class, $this->plugin);
        $this->assertNotEmpty($this->plugin->plugin_path);
        $this->assertNotEmpty($this->plugin->plugin_url);
    }

    public function test_logger_instance()
    {
        $this->assertInstanceOf(WC_Logger::class, $this->plugin->logger);
    }
}