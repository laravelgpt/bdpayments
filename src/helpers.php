<?php

declare(strict_types=1);

if (!function_exists('payment_gateway')) {
    /**
     * Get the payment gateway service instance.
     */
    function payment_gateway(): \BDPayments\LaravelPaymentGateway\Services\PaymentGatewayService
    {
        return app(\BDPayments\LaravelPaymentGateway\Services\PaymentGatewayService::class);
    }
}

if (!function_exists('payment_manager')) {
    /**
     * Get the payment manager service instance.
     */
    function payment_manager(): \BDPayments\LaravelPaymentGateway\Services\PaymentManager
    {
        return app(\BDPayments\LaravelPaymentGateway\Services\PaymentManager::class);
    }
}

if (!function_exists('payment_security')) {
    /**
     * Get the payment security service instance.
     */
    function payment_security(): \BDPayments\LaravelPaymentGateway\Services\PaymentSecurityService
    {
        return app(\BDPayments\LaravelPaymentGateway\Services\PaymentSecurityService::class);
    }
}

if (!function_exists('ai_agent')) {
    /**
     * Get the AI agent service instance.
     */
    function ai_agent(): \BDPayments\LaravelPaymentGateway\Services\AIAgentService
    {
        return app(\BDPayments\LaravelPaymentGateway\Services\AIAgentService::class);
    }
}

if (!function_exists('payment_logger')) {
    /**
     * Get the payment logger service instance.
     */
    function payment_logger(): \BDPayments\LaravelPaymentGateway\Services\PaymentLogger
    {
        return app(\BDPayments\LaravelPaymentGateway\Services\PaymentLogger::class);
    }
}

if (!function_exists('payment_validator')) {
    /**
     * Get the payment validator service instance.
     */
    function payment_validator(): \BDPayments\LaravelPaymentGateway\Services\PaymentValidator
    {
        return app(\BDPayments\LaravelPaymentGateway\Services\PaymentValidator::class);
    }
}

if (!function_exists('invoice_service')) {
    /**
     * Get the invoice service instance.
     */
    function invoice_service(): \BDPayments\LaravelPaymentGateway\Services\InvoiceService
    {
        return app(\BDPayments\LaravelPaymentGateway\Services\InvoiceService::class);
    }
}

if (!function_exists('payment_history_service')) {
    /**
     * Get the payment history service instance.
     */
    function payment_history_service(): \BDPayments\LaravelPaymentGateway\Services\PaymentHistoryService
    {
        return app(\BDPayments\LaravelPaymentGateway\Services\PaymentHistoryService::class);
    }
}

if (!function_exists('payment_gateway_config')) {
    /**
     * Get payment gateway configuration.
     */
    function payment_gateway_config(string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return config('payment-gateway');
        }

        return config("payment-gateway.{$key}", $default);
    }
}

if (!function_exists('payment_gateway_route')) {
    /**
     * Generate payment gateway route URL.
     */
    function payment_gateway_route(string $name, array $parameters = []): string
    {
        return route("payment-gateway.{$name}", $parameters);
    }
}

if (!function_exists('payment_gateway_view')) {
    /**
     * Get payment gateway view.
     */
    function payment_gateway_view(string $view, array $data = []): \Illuminate\View\View
    {
        return view("payment-gateway::{$view}", $data);
    }
}

if (!function_exists('payment_gateway_asset')) {
    /**
     * Get payment gateway asset URL.
     */
    function payment_gateway_asset(string $path): string
    {
        return asset("vendor/payment-gateway/{$path}");
    }
}

if (!function_exists('payment_gateway_trans')) {
    /**
     * Get payment gateway translation.
     */
    function payment_gateway_trans(string $key, array $replace = [], string $locale = null): string
    {
        return trans("payment-gateway::{$key}", $replace, $locale);
    }
}

if (!function_exists('generate_payment_hash')) {
    /**
     * Generate secure payment hash.
     */
    function generate_payment_hash(array $data, string $secret = null): string
    {
        return payment_security()->generatePaymentHash($data, $secret);
    }
}

if (!function_exists('verify_payment_hash')) {
    /**
     * Verify payment hash.
     */
    function verify_payment_hash(array $data, string $hash, string $secret = null): bool
    {
        return payment_security()->verifyPaymentHash($data, $hash, $secret);
    }
}

if (!function_exists('encrypt_payment_data')) {
    /**
     * Encrypt sensitive payment data.
     */
    function encrypt_payment_data(array $data): array
    {
        return payment_security()->encryptPaymentData($data);
    }
}

if (!function_exists('decrypt_payment_data')) {
    /**
     * Decrypt sensitive payment data.
     */
    function decrypt_payment_data(array $data): array
    {
        return payment_security()->decryptPaymentData($data);
    }
}

if (!function_exists('sanitize_payment_data')) {
    /**
     * Sanitize payment data for logging.
     */
    function sanitize_payment_data(array $data): array
    {
        return payment_security()->sanitizeForLogging($data);
    }
}

if (!function_exists('generate_secure_transaction_id')) {
    /**
     * Generate secure transaction ID.
     */
    function generate_secure_transaction_id(): string
    {
        return payment_security()->generateSecureTransactionId();
    }
}

if (!function_exists('generate_secure_reference_id')) {
    /**
     * Generate secure reference ID.
     */
    function generate_secure_reference_id(): string
    {
        return payment_security()->generateSecureReferenceId();
    }
}

if (!function_exists('check_payment_rate_limit')) {
    /**
     * Check payment rate limit.
     */
    function check_payment_rate_limit(string $identifier, int $maxAttempts = 5, int $windowMinutes = 15): bool
    {
        return payment_security()->checkRateLimit($identifier, $maxAttempts, $windowMinutes);
    }
}

if (!function_exists('detect_fraudulent_activity')) {
    /**
     * Detect fraudulent activity.
     */
    function detect_fraudulent_activity(string $ipAddress, array $paymentData): array
    {
        return payment_security()->detectFraudulentActivity($ipAddress, $paymentData);
    }
}

if (!function_exists('get_supported_gateways')) {
    /**
     * Get supported payment gateways.
     */
    function get_supported_gateways(): array
    {
        return payment_manager()->getSupportedGateways();
    }
}

if (!function_exists('is_gateway_supported')) {
    /**
     * Check if gateway is supported.
     */
    function is_gateway_supported(string $gateway): bool
    {
        return payment_manager()->isGatewaySupported($gateway);
    }
}

if (!function_exists('get_optimal_gateway')) {
    /**
     * Get optimal gateway for payment.
     */
    function get_optimal_gateway(array $paymentData): string
    {
        return ai_agent()->suggestOptimalGateway($paymentData);
    }
}

if (!function_exists('analyze_payment_patterns')) {
    /**
     * Analyze payment patterns.
     */
    function analyze_payment_patterns(\BDPayments\LaravelPaymentGateway\Models\Payment $payment): array
    {
        return ai_agent()->analyzePaymentPatterns($payment);
    }
}

if (!function_exists('predict_payment_failure')) {
    /**
     * Predict payment failure.
     */
    function predict_payment_failure(\BDPayments\LaravelPaymentGateway\Models\Payment $payment): array
    {
        return ai_agent()->predictPaymentFailure($payment);
    }
}

if (!function_exists('get_payment_insights')) {
    /**
     * Get payment insights.
     */
    function get_payment_insights(): array
    {
        return ai_agent()->generateInsights();
    }
}

if (!function_exists('auto_resolve_payment_problems')) {
    /**
     * Auto-resolve payment problems.
     */
    function auto_resolve_payment_problems(): array
    {
        return ai_agent()->autoResolveProblems();
    }
}

if (!function_exists('provide_customer_support')) {
    /**
     * Provide customer support.
     */
    function provide_customer_support(string $query, array $context = []): array
    {
        return ai_agent()->provideCustomerSupport($query, $context);
    }
}

if (!function_exists('log_payment')) {
    /**
     * Log payment activity.
     */
    function log_payment(string $action, string $transactionId, array $data = []): void
    {
        payment_logger()->log($action, $transactionId, $data);
    }
}

if (!function_exists('validate_payment_data')) {
    /**
     * Validate payment data.
     */
    function validate_payment_data(array $data, array $rules = []): array
    {
        return payment_validator()->validate($data, $rules);
    }
}

if (!function_exists('generate_invoice')) {
    /**
     * Generate invoice for payment.
     */
    function generate_invoice(\BDPayments\LaravelPaymentGateway\Models\Payment $payment, array $data = []): \BDPayments\LaravelPaymentGateway\Models\Invoice
    {
        return invoice_service()->generateInvoice($payment, $data);
    }
}

if (!function_exists('send_invoice')) {
    /**
     * Send invoice to customer.
     */
    function send_invoice(\BDPayments\LaravelPaymentGateway\Models\Invoice $invoice): bool
    {
        return invoice_service()->sendInvoice($invoice);
    }
}

if (!function_exists('generate_invoice_pdf')) {
    /**
     * Generate invoice PDF.
     */
    function generate_invoice_pdf(\BDPayments\LaravelPaymentGateway\Models\Invoice $invoice): string
    {
        return invoice_service()->generateInvoicePdf($invoice);
    }
}

if (!function_exists('log_payment_history')) {
    /**
     * Log payment history.
     */
    function log_payment_history(\BDPayments\LaravelPaymentGateway\Models\Payment $payment, string $action, array $details = []): \BDPayments\LaravelPaymentGateway\Models\PaymentHistory
    {
        return payment_history_service()->logHistory($payment, $action, $details);
    }
}

if (!function_exists('report_payment_problem')) {
    /**
     * Report payment problem.
     */
    function report_payment_problem(\BDPayments\LaravelPaymentGateway\Models\Payment $payment, string $type, string $title, string $description): \BDPayments\LaravelPaymentGateway\Models\PaymentProblem
    {
        return payment_history_service()->reportProblem($payment, $type, $title, $description);
    }
}

if (!function_exists('get_payment_history')) {
    /**
     * Get payment history.
     */
    function get_payment_history(\BDPayments\LaravelPaymentGateway\Models\Payment $payment): \Illuminate\Database\Eloquent\Collection
    {
        return payment_history_service()->getPaymentHistory($payment);
    }
}
