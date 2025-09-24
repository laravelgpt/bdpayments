<?php

declare(strict_types=1);

namespace BDPayments\LaravelPaymentGateway\Facades;

use Illuminate\Support\Facades\Facade;
use BDPayments\LaravelPaymentGateway\Services\PaymentGatewayService;
use BDPayments\LaravelPaymentGateway\PaymentResponse;

/**
 * Payment Gateway Facade
 * 
 * @method static PaymentResponse initializePayment(string $gateway, array $data)
 * @method static PaymentResponse verifyPayment(string $gateway, string $paymentId)
 * @method static PaymentResponse refundPayment(string $gateway, string $paymentId, float $amount, string $reason = '')
 * @method static PaymentResponse getPaymentStatus(string $gateway, string $paymentId)
 * @method static array getSupportedGateways()
 * @method static bool isGatewaySupported(string $gateway)
 * @method static array getGatewayConfig(string $gateway)
 * @method static void logPayment(string $operation, string $gateway, array $data, ?PaymentResponse $response = null)
 */
class PaymentGateway extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return PaymentGatewayService::class;
    }
}
