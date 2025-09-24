<?php

declare(strict_types=1);

namespace BDPayments\LaravelPaymentGateway\Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use BDPayments\LaravelPaymentGateway\Services\PaymentGatewayService;
use BDPayments\LaravelPaymentGateway\Models\Payment;

class PaymentGatewayTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Set up test configuration
        config([
            'payment-gateway.gateways.nagad' => [
                'merchant_id' => 'test_merchant',
                'merchant_private_key' => 'test_private_key',
                'nagad_public_key' => 'test_public_key',
                'sandbox' => true,
            ],
            'payment-gateway.gateways.bkash' => [
                'app_key' => 'test_app_key',
                'app_secret' => 'test_app_secret',
                'username' => 'test_username',
                'password' => 'test_password',
                'sandbox' => true,
            ],
            'payment-gateway.gateways.binance' => [
                'api_key' => 'test_api_key',
                'secret_key' => 'test_secret_key',
                'sandbox' => true,
            ],
        ]);
    }

    public function testPaymentFormDisplays(): void
    {
        $response = $this->get(route('payment.form', ['gateway' => 'nagad']));
        
        $response->assertStatus(200);
        $response->assertViewIs('payment-gateway::payment-form');
        $response->assertSee('Nagad Payment');
    }

    public function testPaymentInitializationWithValidData(): void
    {
        $paymentData = [
            'gateway' => 'nagad',
            'order_id' => 'ORDER123',
            'amount' => 100.50,
            'currency' => 'BDT',
        ];

        $response = $this->postJson(route('payment.initialize'), $paymentData);
        
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'message',
            'data',
            'payment_id',
            'redirect_url',
            'transaction_id',
            'amount',
            'currency',
            'status',
        ]);
    }

    public function testPaymentInitializationWithInvalidData(): void
    {
        $paymentData = [
            'gateway' => 'nagad',
            'order_id' => '', // Invalid: empty order ID
            'amount' => -100, // Invalid: negative amount
        ];

        $response = $this->postJson(route('payment.initialize'), $paymentData);
        
        $response->assertStatus(422);
        $response->assertJsonStructure([
            'success',
            'message',
            'errors',
        ]);
    }

    public function testPaymentVerification(): void
    {
        $response = $this->getJson(route('payment.verify', [
            'gateway' => 'nagad',
            'payment_id' => 'PAYMENT123'
        ]));
        
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'message',
            'data',
            'payment_id',
            'transaction_id',
            'amount',
            'currency',
            'status',
        ]);
    }

    public function testPaymentRefund(): void
    {
        $refundData = [
            'payment_id' => 'PAYMENT123',
            'amount' => 50.0,
            'reason' => 'Customer request',
        ];

        $response = $this->postJson(route('payment.refund', ['gateway' => 'nagad']), $refundData);
        
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'message',
            'data',
            'refund_id',
            'transaction_id',
            'amount',
            'currency',
            'status',
        ]);
    }

    public function testPaymentStatusCheck(): void
    {
        $response = $this->getJson(route('payment.status', [
            'gateway' => 'nagad',
            'payment_id' => 'PAYMENT123'
        ]));
        
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'message',
            'data',
            'payment_id',
            'transaction_id',
            'amount',
            'currency',
            'status',
        ]);
    }

    public function testPaymentCallback(): void
    {
        $response = $this->get(route('payment.callback', [
            'gateway' => 'nagad',
            'payment_id' => 'PAYMENT123'
        ]));
        
        $response->assertStatus(302); // Redirect to success or failed page
    }

    public function testPaymentWebhook(): void
    {
        $webhookData = [
            'payment_id' => 'PAYMENT123',
            'status' => 'completed',
            'amount' => 100.50,
        ];

        $response = $this->postJson(route('payment.webhook', ['gateway' => 'nagad']), $webhookData);
        
        $response->assertStatus(200);
        $response->assertJson(['status' => 'success']);
    }

    public function testPaymentSuccessPage(): void
    {
        $response = $this->get(route('payment.success', [
            'gateway' => 'nagad',
            'payment_id' => 'PAYMENT123',
            'amount' => 100.50,
            'currency' => 'BDT',
        ]));
        
        $response->assertStatus(200);
        $response->assertViewIs('payment-gateway::success');
        $response->assertSee('Payment Successful');
    }

    public function testPaymentFailedPage(): void
    {
        $response = $this->get(route('payment.failed', [
            'gateway' => 'nagad',
            'error' => 'Payment failed'
        ]));
        
        $response->assertStatus(200);
        $response->assertViewIs('payment-gateway::failed');
        $response->assertSee('Payment Failed');
    }

    public function testSupportedGateways(): void
    {
        $service = app(PaymentGatewayService::class);
        $gateways = $service->getSupportedGateways();
        
        $this->assertArrayHasKey('nagad', $gateways);
        $this->assertArrayHasKey('bkash', $gateways);
        $this->assertArrayHasKey('binance', $gateways);
        $this->assertEquals('Nagad Payment Gateway', $gateways['nagad']);
        $this->assertEquals('bKash Payment Gateway', $gateways['bkash']);
        $this->assertEquals('Binance Payment Gateway', $gateways['binance']);
    }

    public function testGatewaySupportCheck(): void
    {
        $service = app(PaymentGatewayService::class);
        
        $this->assertTrue($service->isGatewaySupported('nagad'));
        $this->assertTrue($service->isGatewaySupported('bkash'));
        $this->assertTrue($service->isGatewaySupported('binance'));
        $this->assertFalse($service->isGatewaySupported('invalid'));
    }

    public function testPaymentModel(): void
    {
        $payment = Payment::create([
            'user_id' => 1,
            'order_id' => 'ORDER123',
            'gateway' => 'nagad',
            'payment_id' => 'PAYMENT123',
            'amount' => 100.50,
            'currency' => 'BDT',
            'status' => 'pending',
        ]);

        $this->assertTrue($payment->isPending());
        $this->assertFalse($payment->isCompleted());
        $this->assertFalse($payment->isFailed());
        $this->assertFalse($payment->isRefunded());

        $payment->markAsCompleted();
        $this->assertTrue($payment->isCompleted());
        $this->assertFalse($payment->isPending());
    }

    public function testPaymentLogging(): void
    {
        $service = app(PaymentGatewayService::class);
        
        $service->logPayment('test_operation', 'nagad', ['test' => 'data']);
        
        // This test verifies that logging doesn't throw exceptions
        $this->assertTrue(true);
    }
}
