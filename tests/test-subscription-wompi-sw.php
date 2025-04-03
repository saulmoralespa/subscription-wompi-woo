<?php

class Test_Subscription_Wompi_SW extends WP_UnitTestCase
{

    protected function setUp(): void
    {
        parent::setUp();
        if (!function_exists('subscription_wompi_sw')) {
            require_once dirname(__DIR__) . '/subscription-wompi-woo.php';
        }
    }

    public function test_plugin_loaded()
    {
        $this->assertTrue(function_exists('subscription_wompi_sw'), 'La función principal del plugin no está definida.');
    }

    public function test_requirements_met()
    {
        // Simulamos que los requisitos están cumplidos
        update_option('woocommerce_currency', 'COP');
        $this->assertTrue(subscription_wompi_sw_requirements(), 'Los requisitos del plugin no se cumplen.');
    }

    public function test_load_subscription_wompi_sw_plugin() {
        // Cargar la clase manualmente si no está incluida
        if (!class_exists('Subscription_Wompi_SW_Plugin')) {
            require_once dirname(__DIR__) . '/includes/class-subscription-wompi-sw-plugin.php';
        }

        // Verificar si la clase existe
        $this->assertTrue(class_exists('Subscription_Wompi_SW_Plugin'), 'La clase Subscription_Wompi_SW_Plugin no se ha cargado.');
    }
}