<?php

interface ConfigStrategyInterface
{
    public function getEnv(): string;
    public function getApiUrl(): string;
    public function getPluginUrl(): string;
    public function getPublicKeyBase64(): string;
    public function getPlatformUrl(): string;
    public function getWebhookUrl(string $gatewayClass): string;
} 