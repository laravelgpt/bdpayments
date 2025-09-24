<?php

declare(strict_types=1);

namespace BDPayments\NagadBkashGateway\Tests;

use PHPUnit\Framework\TestCase;
use BDPayments\NagadBkashGateway\PaymentFactory;
use BDPayments\NagadBkashGateway\Exceptions\ConfigurationException;

class PaymentFactoryTest extends TestCase
{
    public function testCreateNagadGateway(): void
    {
        $config = [
            'merchant_id' => 'test_merchant',
            'merchant_private_key' => 'test_private_key',
            'nagad_public_key' => 'test_public_key',
            'sandbox' => true,
        ];

        $gateway = PaymentFactory::create('nagad', $config);

        $this->assertInstanceOf(\BDPayments\NagadBkashGateway\Gateways\NagadGateway::class, $gateway);
        $this->assertEquals('nagad', $gateway->getGatewayName());
    }

    public function testCreateBkashGateway(): void
    {
        $config = [
            'app_key' => 'test_app_key',
            'app_secret' => 'test_app_secret',
            'username' => 'test_username',
            'password' => 'test_password',
            'sandbox' => true,
        ];

        $gateway = PaymentFactory::create('bkash', $config);

        $this->assertInstanceOf(\BDPayments\NagadBkashGateway\Gateways\BkashGateway::class, $gateway);
        $this->assertEquals('bkash', $gateway->getGatewayName());
    }

    public function testCreateWithInvalidGateway(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('Unsupported gateway: invalid');

        PaymentFactory::create('invalid', []);
    }

    public function testCreateNagadWithMissingConfig(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('Missing required Nagad configuration: merchant_id');

        PaymentFactory::create('nagad', []);
    }

    public function testCreateBkashWithMissingConfig(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('Missing required bKash configuration: app_key');

        PaymentFactory::create('bkash', []);
    }

    public function testGetSupportedGateways(): void
    {
        $gateways = PaymentFactory::getSupportedGateways();

        $this->assertArrayHasKey('nagad', $gateways);
        $this->assertArrayHasKey('bkash', $gateways);
        $this->assertEquals('Nagad Payment Gateway', $gateways['nagad']);
        $this->assertEquals('bKash Payment Gateway', $gateways['bkash']);
    }

    public function testIsGatewaySupported(): void
    {
        $this->assertTrue(PaymentFactory::isGatewaySupported('nagad'));
        $this->assertTrue(PaymentFactory::isGatewaySupported('bkash'));
        $this->assertTrue(PaymentFactory::isGatewaySupported('NAGAD'));
        $this->assertTrue(PaymentFactory::isGatewaySupported('BKASH'));
        $this->assertFalse(PaymentFactory::isGatewaySupported('invalid'));
    }
}
