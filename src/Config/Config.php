<?php

declare(strict_types=1);

namespace BDPayments\NagadBkashGateway\Config;

use BDPayments\NagadBkashGateway\Exceptions\ConfigurationException;

/**
 * Configuration management class
 */
class Config
{
    private array $config = [];

    public function __construct(array $config = [])
    {
        $this->config = $config;
    }

    /**
     * Load configuration from array
     *
     * @param array $config
     * @return self
     */
    public function load(array $config): self
    {
        $this->config = array_merge($this->config, $config);
        return $this;
    }

    /**
     * Load configuration from file
     *
     * @param string $filePath
     * @return self
     * @throws ConfigurationException
     */
    public function loadFromFile(string $filePath): self
    {
        if (!file_exists($filePath)) {
            throw new ConfigurationException("Configuration file not found: {$filePath}");
        }

        $config = include $filePath;
        
        if (!is_array($config)) {
            throw new ConfigurationException("Invalid configuration file format: {$filePath}");
        }

        return $this->load($config);
    }

    /**
     * Get configuration value
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $keys = explode('.', $key);
        $value = $this->config;

        foreach ($keys as $k) {
            if (!is_array($value) || !array_key_exists($k, $value)) {
                return $default;
            }
            $value = $value[$k];
        }

        return $value;
    }

    /**
     * Set configuration value
     *
     * @param string $key
     * @param mixed $value
     * @return self
     */
    public function set(string $key, mixed $value): self
    {
        $keys = explode('.', $key);
        $config = &$this->config;

        foreach ($keys as $k) {
            if (!isset($config[$k]) || !is_array($config[$k])) {
                $config[$k] = [];
            }
            $config = &$config[$k];
        }

        $config = $value;
        return $this;
    }

    /**
     * Check if configuration key exists
     *
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool
    {
        return $this->get($key) !== null;
    }

    /**
     * Get all configuration
     *
     * @return array
     */
    public function all(): array
    {
        return $this->config;
    }

    /**
     * Get gateway configuration
     *
     * @param string $gatewayName
     * @return array
     * @throws ConfigurationException
     */
    public function getGatewayConfig(string $gatewayName): array
    {
        $config = $this->get("gateways.{$gatewayName}");
        
        if (!$config) {
            throw new ConfigurationException("Gateway configuration not found: {$gatewayName}");
        }

        return $config;
    }

    /**
     * Validate gateway configuration
     *
     * @param string $gatewayName
     * @return bool
     * @throws ConfigurationException
     */
    public function validateGatewayConfig(string $gatewayName): bool
    {
        $config = $this->getGatewayConfig($gatewayName);

        if (!isset($config['gateway'])) {
            throw new ConfigurationException("Gateway type not specified for '{$gatewayName}'");
        }

        $gateway = $config['gateway'];
        $requiredFields = $this->getRequiredFields($gateway);

        foreach ($requiredFields as $field) {
            if (!isset($config[$field]) || empty($config[$field])) {
                throw new ConfigurationException("Missing required field '{$field}' for gateway '{$gatewayName}'");
            }
        }

        return true;
    }

    /**
     * Get required fields for a gateway
     *
     * @param string $gateway
     * @return array
     */
    private function getRequiredFields(string $gateway): array
    {
        return match (strtolower($gateway)) {
            'nagad' => ['merchant_id', 'merchant_private_key', 'nagad_public_key'],
            'bkash' => ['app_key', 'app_secret', 'username', 'password'],
            default => throw new ConfigurationException("Unknown gateway type: {$gateway}"),
        };
    }
}
