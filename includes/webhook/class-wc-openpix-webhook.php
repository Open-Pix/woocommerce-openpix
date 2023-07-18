<?php

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

        if (isset($data['charge']) && isset($data['charge']['correlationID'])) {
            return false;
        }

        return true;
    }

    public function validateWebhook($data, $body)
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
}