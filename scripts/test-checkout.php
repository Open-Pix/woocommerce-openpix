<?php
/**
 * Test script for Pix and Boleto checkout via OpenPix API.
 *
 * Verifies that each gateway (Pix and Boleto) uses the correct API URL
 * based on its own environment setting (config per-gateway).
 *
 * Usage:
 *   docker exec wordpress wp eval-file \
 *     /var/www/html/wp-content/plugins/woocommerce-openpix/scripts/test-checkout.php \
 *     --allow-root
 */

if (!defined('ABSPATH')) {
    echo "This script must be run via: wp eval-file\n";
    exit(1);
}

echo "=== OpenPix Checkout Test ===\n\n";

// 1. Show config for each gateway
echo "--- Gateway Configuration ---\n\n";

$pix_gateway = WC_OpenPix_Pix_Gateway::instance();
$pix_settings = get_option('woocommerce_woocommerce_openpix_pix_settings', []);
$pix_environment = isset($pix_settings['environment'])
    ? $pix_settings['environment']
    : 'prod';
$pix_config = ConfigFactory::createStrategy($pix_environment);

echo "Pix Gateway:\n";
echo "  Environment: $pix_environment\n";
echo '  API URL:     ' . $pix_config->getApiUrl() . "\n";
echo '  AppID:       ' .
    (empty($pix_gateway->appID)
        ? '(not set)'
        : substr($pix_gateway->appID, 0, 20) . '...') .
    "\n\n";

$boleto_gateway = WC_OpenPix_Boleto_Gateway::instance();
$boleto_settings = get_option(
    'woocommerce_woocommerce_openpix_boleto_settings',
    []
);
$boleto_environment = isset($boleto_settings['environment'])
    ? $boleto_settings['environment']
    : 'prod';
$boleto_config = ConfigFactory::createStrategy($boleto_environment);

echo "Boleto Gateway:\n";
echo "  Environment: $boleto_environment\n";
echo '  API URL:     ' . $boleto_config->getApiUrl() . "\n";
echo '  AppID:       ' .
    (empty($boleto_gateway->appID)
        ? '(not set)'
        : substr($boleto_gateway->appID, 0, 20) . '...') .
    "\n\n";

// 2. Create test WooCommerce order
echo "--- Creating Test Order ---\n\n";

$order = wc_create_order();
$order->set_address(
    [
        'first_name' => 'Teste',
        'last_name' => 'OpenPix',
        'email' => 'teste@openpix.com',
        'phone' => '11999999999',
        'address_1' => 'Rua Teste 123',
        'address_2' => 'Apto 1',
        'city' => 'Sao Paulo',
        'state' => 'SP',
        'postcode' => '01001000',
        'country' => 'BR',
    ],
    'billing'
);

$order->set_total('10.00');
$order->save();

$order_id = $order->get_id();
echo "Order #$order_id created.\n\n";

// Helper: make API request
function test_charge_request($config, $appID, $payload, $label)
{
    $url = $config->getApiUrl() . '/api/v1/charge';

    $params = [
        'timeout' => 60,
        'headers' => [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'Authorization' => $appID,
            'version' => WC_OpenPix::VERSION,
            'platform' => 'WOOCOMMERCE',
        ],
        'body' => wp_json_encode($payload),
        'method' => 'POST',
        'data_format' => 'body',
    ];

    echo "[$label] POST $url\n";

    if ($config->getEnv() === 'development') {
        $response = wp_remote_post($url, $params);
    } else {
        $response = wp_safe_remote_post($url, $params);
    }

    if (is_wp_error($response)) {
        echo "[$label] WP Error: " . $response->get_error_message() . "\n";
        return null;
    }

    $code = $response['response']['code'];
    $body = $response['body'];

    echo "[$label] HTTP Status: $code\n";
    echo "[$label] Response: $body\n";

    return $response;
}

// 3. Test Pix charge
echo "--- Test Pix Charge ---\n\n";

if (empty($pix_gateway->appID)) {
    echo "[Pix] SKIPPED: AppID not configured.\n\n";
} else {
    $pix_correlation_id = 'test-pix-' . wp_generate_uuid4();
    $pix_payload = [
        'correlationID' => $pix_correlation_id,
        'value' => 1000,
        'comment' => 'Test Pix charge from test-checkout.php',
    ];

    test_charge_request($pix_config, $pix_gateway->appID, $pix_payload, 'Pix');
    echo "\n";
}

// 4. Test Boleto charge
echo "--- Test Boleto Charge ---\n\n";

if (empty($boleto_gateway->appID)) {
    echo "[Boleto] SKIPPED: AppID not configured.\n\n";
} else {
    $boleto_correlation_id = 'test-boleto-' . wp_generate_uuid4();
    $boleto_payload = [
        'correlationID' => $boleto_correlation_id,
        'value' => 1000,
        'type' => 'BOLETO',
        'comment' => 'Test Boleto charge from test-checkout.php',
        'customer' => [
            'name' => 'Teste OpenPix',
            'email' => 'teste@openpix.com',
            'taxID' => '12345678909',
            'phone' => '5511999999999',
            'address' => [
                'zipcode' => '01001000',
                'street' => 'Rua Teste',
                'number' => '123',
                'neighborhood' => 'Centro',
                'city' => 'Sao Paulo',
                'state' => 'SP',
                'country' => 'BR',
                'complement' => 'Apto 1',
            ],
        ],
    ];

    test_charge_request(
        $boleto_config,
        $boleto_gateway->appID,
        $boleto_payload,
        'Boleto'
    );
    echo "\n";
}

// 5. Cleanup
echo "--- Cleanup ---\n\n";
$order->delete(true);
echo "Order #$order_id deleted.\n\n";

echo "=== Done ===\n";
