<?php

declare(strict_types=1);

namespace BDPayments\LaravelPaymentGateway\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PaymentGatewaySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Seed payment gateway configurations
        $this->seedPaymentConfigurations();
        
        // Seed sample payment data (for testing)
        if (app()->environment('local', 'testing')) {
            $this->seedSamplePayments();
        }
    }

    /**
     * Seed payment gateway configurations
     */
    private function seedPaymentConfigurations(): void
    {
        $configurations = [
            [
                'key' => 'payment-gateway.default_gateway',
                'value' => 'nagad',
                'description' => 'Default payment gateway',
            ],
            [
                'key' => 'payment-gateway.logging.enabled',
                'value' => 'true',
                'description' => 'Enable payment logging',
            ],
            [
                'key' => 'payment-gateway.rate_limits.nagad.max_attempts',
                'value' => '60',
                'description' => 'Nagad rate limit max attempts',
            ],
            [
                'key' => 'payment-gateway.rate_limits.bkash.max_attempts',
                'value' => '60',
                'description' => 'bKash rate limit max attempts',
            ],
            [
                'key' => 'payment-gateway.rate_limits.binance.max_attempts',
                'value' => '60',
                'description' => 'Binance rate limit max attempts',
            ],
        ];

        foreach ($configurations as $config) {
            DB::table('configurations')->updateOrInsert(
                ['key' => $config['key']],
                $config
            );
        }
    }

    /**
     * Seed sample payment data for testing
     */
    private function seedSamplePayments(): void
    {
        $samplePayments = [
            [
                'user_id' => 1,
                'order_id' => 'ORDER_' . time() . '_001',
                'gateway' => 'nagad',
                'payment_id' => 'NAGAD_' . time() . '_001',
                'transaction_id' => 'TXN_' . time() . '_001',
                'amount' => 100.00,
                'currency' => 'BDT',
                'status' => 'completed',
                'gateway_response' => json_encode([
                    'status' => 'Success',
                    'message' => 'Payment completed successfully',
                    'transactionId' => 'TXN_' . time() . '_001',
                ]),
                'processed_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id' => 1,
                'order_id' => 'ORDER_' . time() . '_002',
                'gateway' => 'bkash',
                'payment_id' => 'BKASH_' . time() . '_002',
                'transaction_id' => 'TXN_' . time() . '_002',
                'amount' => 250.50,
                'currency' => 'BDT',
                'status' => 'pending',
                'gateway_response' => json_encode([
                    'status' => 'Pending',
                    'message' => 'Payment is being processed',
                ]),
                'expires_at' => now()->addHours(24),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id' => 1,
                'order_id' => 'ORDER_' . time() . '_003',
                'gateway' => 'binance',
                'payment_id' => 'BINANCE_' . time() . '_003',
                'transaction_id' => 'TXN_' . time() . '_003',
                'amount' => 50.00,
                'currency' => 'USDT',
                'status' => 'completed',
                'gateway_response' => json_encode([
                    'status' => 'SUCCESS',
                    'message' => 'Payment completed successfully',
                    'transactionId' => 'TXN_' . time() . '_003',
                ]),
                'processed_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($samplePayments as $payment) {
            DB::table('payments')->insert($payment);
        }

        // Seed sample payment logs
        $this->seedSamplePaymentLogs();
    }

    /**
     * Seed sample payment logs
     */
    private function seedSamplePaymentLogs(): void
    {
        $sampleLogs = [
            [
                'payment_id' => 1,
                'gateway' => 'nagad',
                'operation' => 'initialize_payment',
                'status' => 'success',
                'message' => 'Payment initialized successfully',
                'request_data' => json_encode([
                    'order_id' => 'ORDER_' . time() . '_001',
                    'amount' => 100.00,
                    'currency' => 'BDT',
                ]),
                'response_data' => json_encode([
                    'success' => true,
                    'payment_id' => 'NAGAD_' . time() . '_001',
                    'redirect_url' => 'https://nagad.com/pay/NAGAD_' . time() . '_001',
                ]),
                'ip_address' => '127.0.0.1',
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'payment_id' => 1,
                'gateway' => 'nagad',
                'operation' => 'verify_payment',
                'status' => 'success',
                'message' => 'Payment verified successfully',
                'request_data' => json_encode([
                    'payment_id' => 'NAGAD_' . time() . '_001',
                ]),
                'response_data' => json_encode([
                    'success' => true,
                    'status' => 'completed',
                    'amount' => 100.00,
                    'currency' => 'BDT',
                ]),
                'ip_address' => '127.0.0.1',
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($sampleLogs as $log) {
            DB::table('payment_logs')->insert($log);
        }
    }
}
