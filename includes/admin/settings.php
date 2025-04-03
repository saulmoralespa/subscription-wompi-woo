<?php

wc_enqueue_js( "
    jQuery(function($) {
        const selectors = {
            subscriptionWompiFields: '#woocommerce_subscription_wompi_sw_key_private, #woocommerce_subscription_wompi_sw_key_public, #woocommerce_subscription_wompi_sw_key_integrety',
            subscriptionWompiSandboxFields: '#woocommerce_subscription_wompi_sw_sandbox_key_private, #woocommerce_subscription_wompi_sw_sandbox_key_public, #woocommerce_subscription_wompi_sw_sandbox_key_integrety',
            environmentSelector: '#woocommerce_subscription_wompi_sw_environment'
        };
        
        function toggleFields() {
            const {
                subscriptionWompiFields,
                subscriptionWompiSandboxFields,
                environmentSelector
            } = selectors;
            
            const isProduction = $(environmentSelector).val() === '0';
            const paymentFields = isProduction ? subscriptionWompiFields : subscriptionWompiSandboxFields;
            
            $(subscriptionWompiSandboxFields + ',' + subscriptionWompiFields).closest('tr').hide();
            
            $(paymentFields).closest('tr').show();
        }
        
        $(selectors.environmentSelector).change(toggleFields).change();
    });
");

$docs = "<p>Documentación de <a target='_blank' href='https://docs.wompi.co/docs/colombia/ambientes-y-llaves/'>ambientes y llaves</a></p>";

return apply_filters("subscription_wompi_sw_settings",
    array(
        'enabled' => array(
            'title' => __('Habilitar/Deshabilitar'),
            'type' => 'checkbox',
            'label' => __('Habilitar Subscription Wompi'),
            'default' => 'no'
        ),
        'title' => array(
            'title' => __('Título'),
            'type' => 'text',
            'description' => __('Corresponde al título que el usuario ve durante el checkout'),
            'default' => __('Suscripción con Wompi'),
            'desc_tip' => true,
        ),
        'description' => array(
            'title' => __('Descripción'),
            'type' => 'textarea',
            'description' => __('Corresponde a la descripción que el usuario verá durante el checkout'),
            'default' => __('Suscripción a través de Wompi'),
            'desc_tip' => true,
        ),
        'debug' => array(
            'title' => __('Depurador'),
            'type' => 'checkbox',
            'label' => __('Registros de depuración, se guarda en el registro de pago'),
            'default' => 'no'
        ),
        'environment' => array(
            'title' => __('Ambiente'),
            'type'        => 'select',
            'class'       => 'wc-enhanced-select',
            'description' => __('Entorno de pruebas o producción'),
            'desc_tip' => true,
            'default' => true,
            'options'     => array(
                0    => __( 'Producción' ),
                1 => __( 'Pruebas' ),
            ),
        ),
        'api'  => array(
            'title' => __( 'Credenciales API' ),
            'type'  => 'title',
            'description' => $docs
        ),
        'sandbox_key_public' => array(
            'title' => __( 'Llave pública' ),
            'type'  => 'text',
            'description' => __( 'Llave pública para el entorno de pruebas' ),
            'desc_tip' => false
        ),
        'sandbox_key_private' => array(
            'title' => __( 'Llave privada' ),
            'type'  => 'password',
            'description' => __( 'Llave privada para el entorno de pruebas' ),
            'desc_tip' => false
        ),
        'sandbox_key_integrety' => array(
            'title' => __( 'Llave de integridad' ),
            'type'  => 'text',
            'description' => __( 'Llave de integridad para el entorno de pruebas' ),
            'desc_tip' => false
        ),
        'key_public' => array(
            'title' => __( 'Llave pública' ),
            'type'  => 'text',
            'description' => __( 'Llave pública para el entorno de producción' ),
            'desc_tip' => false
        ),
        'key_private' => array(
            'title' => __( 'Llave privada' ),
            'type'  => 'password',
            'description' => __( 'Llave privada para el entorno de producción' ),
            'desc_tip' => false
        ),
        'key_integrety' => array(
            'title' => __( 'Llave de integridad' ),
            'type'  => 'text',
            'description' => __( 'Llave de integridad para el entorno de producción' ),
            'desc_tip' => false
        )
    )
);