<?php

use JetBrains\PhpStorm\NoReturn;

define('__ROOT__', dirname(dirname(__FILE__)));

require_once __ROOT__ . '/config/config.php';

class WC_OpenPix_Webhook
{
    function validSignature($payload, $signature)
    {
        $publicKey = base64_decode(OpenPixConfig::$OPENPIX_PUBLIC_KEY_BASE64);

        $verify = openssl_verify(
            $payload,
            base64_decode($signature),
            $publicKey,
            'sha256WithRSAEncryption'
        );

        return $verify === 1 ? true : false;
    }

    /**
     * Check if the provided data is a valid webhook payload.
     *
     * @param array $data The data to be validated.
     *
     * @return bool Returns true if the data contains any of the required keys (charge, pix, or event), otherwise returns false.
     */
    public function isValidWebhookPayload($data)
    {
        if (!isset($data['event']) || empty($data['event'])) {
            if (!isset($data['evento']) || empty($data['evento'])) {
                return false;
            }
        }

        // @todo remove it and update evento to event

        return true;
    }

    public function isPixDetachedPayload($data): bool
    {
        if (!isset($data['pix'])) {
            return false;
        }

        if (isset($data['charge']['correlationID'])) {
            return false;
        }

        return true;
    }

    public function validateWebhook($data, $body): void
    {
        $signature = $_SERVER['HTTP_X_WEBHOOK_SIGNATURE'] ?? null;

        if (!$signature || !$this->validSignature($body, $signature)) {
            header('HTTP/1.2 400 Bad Request');
            $response = [
                'error' => 'Invalid Webhook signature',
            ];
            echo json_encode($response);
            exit();
        }

        if (!$this->isValidWebhookPayload($data)) {
            header('HTTP/1.2 400 Bad Request');
            $response = [
                'error' => 'Invalid Webhook Payload',
            ];
            echo json_encode($response);
            exit();
        }

        if ($this->isPixDetachedPayload($data)) {
            header('HTTP/1.1 200 OK');

            $response = [
                'message' => 'Pix Detached',
            ];
            echo json_encode($response);
            exit();
        }
    }

    #[NoReturn] public function handleTestWebhook($data)
    {
        WC_OpenPix::debug('handleTestWebhook');
        header('HTTP/1.1 200 OK');
        $response = [
            'message' => 'success',
        ];
        echo json_encode($response);
        exit();
    }

    #[NoReturn] public function handleIntegrationConfiguration($data, $appID): void
    {
        $hasAppID = isset($data['appID']);

        if ($appID) {
            header('HTTP/1.1 400 Bad Request');
            $response = [
                'message' => __('App ID already configured', 'openpix'),
            ];
            echo json_encode($response);
            exit();
        }

        if (!$hasAppID) {
            header('HTTP/1.1 400 Bad Request');
            $response = [
                'message' => __('App ID is required', 'openpix'),
            ];
            echo json_encode($response);
            exit();
        }

        $this->configureIntegration($data);

        header('HTTP/1.1 200 OK');
        $response = [
            'message' => 'success',
        ];
        echo json_encode($response);
        exit();
    }

    public function configureIntegration($data): void
    {
        $this->update_option('appID', $data['appID']);
    }

    public function get_order_id_by_correlation_id($correlation_id)
    {
        global $wpdb;

        if (empty($correlation_id)) {
            return false;
        }

        $order_id = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT DISTINCT ID FROM $wpdb->posts as posts LEFT JOIN $wpdb->postmeta as meta ON posts.ID = meta.post_id WHERE meta.meta_value = %s AND meta.meta_key = %s",
                $correlation_id,
                'openpix_correlation_id'
            )
        );

        if (!empty($order_id)) {
            return $order_id;
        }

        return false;
    }

    public function handleWebhookOrderUpdate($data)
    {
        global $wpdb;
        $correlationID = $data['charge']['correlationID'];
        $status = $data['charge']['status'];
        $endToEndId = $data['pix']['endToEndId'];

        $order_id = $this->get_order_id_by_correlation_id($correlationID);

        if (!$order_id) {
            WC_OpenPix::debug(
                'Cound not find order with correlation ID ' . $correlationID
            );
            header('HTTP/1.1 200 OK');
            $response = [
                'message' => 'fail',
                'error' => 'order not found',
                'order_id' => $order_id,
                'correlationId' => $correlationID,
                'status' => $status,
            ];
            echo json_encode($response);
            exit();
        }

        $order = wc_get_order($order_id);

        if (!$order) {
            WC_OpenPix::debug(
                'Cound not find order with correlation ID ' . $correlationID
            );
            header('HTTP/1.1 200 OK');
            $response = [
                'message' => 'fail',
                'error' => 'order not found',
                'order_id' => $order_id,
                'correlationId' => $correlationID,
                'status' => $status,
            ];
            echo json_encode($response);
            exit();
        }

        $order_correlation_id = get_post_meta(
            $order->id,
            'openpix_correlation_id',
            true
        );
        $order_end_to_end_id = get_post_meta(
            $order->id,
            'openpix_endToEndId',
            true
        );

        if ($order_end_to_end_id) {
            WC_OpenPix::debug('Order already paid ' . $order_id);

            header('HTTP/1.1 200 OK');
            $response = [
                'message' => 'fail',
                'error' => 'order already with end to end id',
                'order_id' => $order_id,
                'correlationId' => $correlationID,
                'status' => $status,
            ];

            echo json_encode($response);
            exit();
        }

        if (!$order_correlation_id) {
            WC_OpenPix::debug('Order without correlation id ' . $order_id);

            header('HTTP/1.1 200 OK');
            $response = [
                'message' => 'fail',
                'error' => 'order without correlation id',
                'order_id' => $order_id,
                'correlationId' => $correlationID,
                'status' => $status,
            ];

            echo json_encode($response);
            exit();
        }

        if ($order_correlation_id !== $correlationID) {
            WC_OpenPix::debug(
                'Order with different correlation id then webhook correlation id ' .
                $order_id
            );

            header('HTTP/1.1 200 OK');
            $response = [
                'message' => 'fail',
                'error' =>
                    'order with different correlation id ' .
                    $order_correlation_id,
                'order_id' => $order_id,
                'correlationId' => $correlationID,
                'status' => $status,
            ];

            echo json_encode($response);
            exit();
        }

        $order_status = $order->get_status();

        $statuses =
            strpos($order_status, 'wc-') === false
                ? ['processing', 'completed']
                : ['wc-processing', 'wc-completed'];
        $already_paid = in_array($order_status, $statuses) ? true : false;

        WC_OpenPix::debug('ipn');
        WC_OpenPix::debug('already paid ' . $already_paid ? 'yes' : 'no');
        WC_OpenPix::debug('status ' . $status);
        WC_OpenPix::debug('correlationID ' . $correlationID);
        WC_OpenPix::debug('endToEndId ' . $endToEndId);

        if (!$already_paid) {
            if ($order) {
                if ($status === 'COMPLETED') {
                    // Changing the order for processing and reduces the stock.

                    $order->update_status(
                        $this->status_when_paid,
                        __('OpenPix: Transaction paid', 'woocommerce-openpix')
                    );

                    $order->payment_complete();

                    // add endToEndId to meta data order
                    $meta_data = [
                        'openpix_endToEndId' => $endToEndId,
                    ];

                    // WooCommerce 3.0 or later
                    if (!method_exists($order, 'update_meta_data')) {
                        foreach ($meta_data as $key => $value) {
                            update_post_meta($id, $key, $value);
                        }
                    } else {
                        foreach ($meta_data as $key => $value) {
                            $order->update_meta_data($key, $value);
                        }

                        $order->save();
                    }
                }
            }
        }

        header('HTTP/1.1 200 OK');

        $response = [
            'message' => 'success',
            'order_id' => $order_id,
            'correlationId' => $correlationID,
            'status' => $status,
        ];

        echo json_encode($response);
        exit();
    }

    public function handleWebhookEvents($data, $body)
    {
        $event = $data['event'];
        // @todo: refactor this to follow event instead evento
        $evento = $data['evento'];

        if ($evento === 'teste_webhook') {
            $this->handleTestWebhook($data);
        }

        if ($event === 'woocommerce-configure') {
            $this->handleIntegrationConfiguration($data);
            return;
        }

        if (
            $event === 'OPENPIX:TRANSACTION_RECEIVED' ||
            $event === 'OPENPIX:CHARGE_COMPLETED'
        ) {
            $this->handleWebhookOrderUpdate($data);
            return;
        }
    }
}