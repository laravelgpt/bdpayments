<?php

declare(strict_types=1);

namespace BDPayments\NagadBkashGateway\Tests\Validators;

use PHPUnit\Framework\TestCase;
use BDPayments\NagadBkashGateway\Validators\PaymentValidator;
use BDPayments\NagadBkashGateway\Exceptions\ValidationException;

class PaymentValidatorTest extends TestCase
{
    public function testValidatePaymentDataWithValidData(): void
    {
        $data = [
            'order_id' => 'ORDER123',
            'amount' => 100.50,
            'currency' => 'BDT',
            'callback_url' => 'https://example.com/callback',
        ];

        $this->assertTrue(PaymentValidator::validatePaymentData($data));
    }

    public function testValidatePaymentDataWithMissingOrderId(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage("Required field 'order_id' is missing or empty");

        PaymentValidator::validatePaymentData(['amount' => 100]);
    }

    public function testValidatePaymentDataWithMissingAmount(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage("Required field 'amount' is missing or empty");

        PaymentValidator::validatePaymentData(['order_id' => 'ORDER123']);
    }

    public function testValidatePaymentDataWithInvalidAmount(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Amount must be a positive number');

        PaymentValidator::validatePaymentData([
            'order_id' => 'ORDER123',
            'amount' => -100,
        ]);
    }

    public function testValidatePaymentDataWithInvalidCurrency(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Currency must be BDT or USD');

        PaymentValidator::validatePaymentData([
            'order_id' => 'ORDER123',
            'amount' => 100,
            'currency' => 'EUR',
        ]);
    }

    public function testValidatePaymentDataWithInvalidCallbackUrl(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Invalid callback URL format');

        PaymentValidator::validatePaymentData([
            'order_id' => 'ORDER123',
            'amount' => 100,
            'callback_url' => 'invalid-url',
        ]);
    }

    public function testValidatePaymentIdWithValidId(): void
    {
        $this->assertTrue(PaymentValidator::validatePaymentId('PAYMENT123'));
    }

    public function testValidatePaymentIdWithEmptyId(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Payment ID cannot be empty');

        PaymentValidator::validatePaymentId('');
    }

    public function testValidateRefundDataWithValidData(): void
    {
        $this->assertTrue(PaymentValidator::validateRefundData('PAYMENT123', 50.0, 'Customer request'));
    }

    public function testValidateRefundDataWithInvalidAmount(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Refund amount must be positive');

        PaymentValidator::validateRefundData('PAYMENT123', -50.0);
    }

    public function testSanitizePaymentData(): void
    {
        $data = [
            'order_id' => '  ORDER123  ',
            'amount' => '100.50',
            'currency' => 'bdt',
            'callback_url' => 'https://example.com/callback',
        ];

        $sanitized = PaymentValidator::sanitizePaymentData($data);

        $this->assertEquals('ORDER123', $sanitized['order_id']);
        $this->assertEquals(100.50, $sanitized['amount']);
        $this->assertEquals('BDT', $sanitized['currency']);
        $this->assertEquals('https://example.com/callback', $sanitized['callback_url']);
    }
}
