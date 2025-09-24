<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Failed - {{ ucfirst($gateway) }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .failed-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        .failed-icon {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, #dc3545, #e74c3c);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
            color: white;
            margin: 0 auto 30px;
            animation: shake 0.5s ease-in-out;
        }
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }
        .gateway-badge {
            font-size: 14px;
            padding: 8px 16px;
            border-radius: 20px;
        }
        .nagad-badge { background: linear-gradient(135deg, #00A651, #00C851); color: white; }
        .bkash-badge { background: linear-gradient(135deg, #E2136E, #F2136E); color: white; }
        .binance-badge { background: linear-gradient(135deg, #F3BA2F, #F3BA2F); color: white; }
        .error-details {
            background: #f8f9fa;
            border-left: 4px solid #dc3545;
            padding: 15px;
            border-radius: 5px;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="card failed-card mt-5">
                    <div class="card-body p-5 text-center">
                        <!-- Failed Icon -->
                        <div class="failed-icon">
                            <i class="fas fa-times"></i>
                        </div>

                        <!-- Failed Message -->
                        <h2 class="text-danger mb-3">Payment Failed</h2>
                        <p class="text-muted mb-4">Unfortunately, your payment could not be processed.</p>

                        <!-- Gateway Badge -->
                        <div class="mb-4">
                            <span class="gateway-badge {{ $gateway }}-badge">
                                <i class="fas fa-credit-card me-2"></i>
                                {{ ucfirst($gateway) }} Payment
                            </span>
                        </div>

                        <!-- Error Details -->
                        @if($error)
                        <div class="error-details mb-4 text-start">
                            <h6 class="text-danger">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                Error Details
                            </h6>
                            <p class="mb-0">{{ $error }}</p>
                        </div>
                        @endif

                        <!-- Common Reasons -->
                        <div class="card bg-light mb-4">
                            <div class="card-body text-start">
                                <h6 class="card-title">
                                    <i class="fas fa-lightbulb me-2"></i>
                                    Common Reasons for Payment Failure
                                </h6>
                                <ul class="mb-0">
                                    <li>Insufficient funds in your account</li>
                                    <li>Incorrect payment information</li>
                                    <li>Network connectivity issues</li>
                                    <li>Payment gateway temporarily unavailable</li>
                                    <li>Transaction timeout</li>
                                </ul>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="d-grid gap-2">
                            <button class="btn btn-primary" onclick="retryPayment()">
                                <i class="fas fa-redo me-2"></i>
                                Try Again
                            </button>
                            <button class="btn btn-outline-secondary" onclick="contactSupport()">
                                <i class="fas fa-headset me-2"></i>
                                Contact Support
                            </button>
                        </div>

                        <!-- Help Information -->
                        <div class="mt-4">
                            <div class="alert alert-warning">
                                <i class="fas fa-info-circle me-2"></i>
                                <strong>Need Help?</strong>
                                <ul class="mb-0 mt-2 text-start">
                                    <li>Check your account balance</li>
                                    <li>Verify your payment information</li>
                                    <li>Try a different payment method</li>
                                    <li>Contact our support team</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Support Information -->
                <div class="text-center mt-4">
                    <p class="text-muted">
                        <i class="fas fa-headset me-2"></i>
                        Still having trouble? We're here to help!
                    </p>
                    <div class="d-flex justify-content-center gap-3">
                        <a href="mailto:support@example.com" class="btn btn-outline-danger btn-sm">
                            <i class="fas fa-envelope me-1"></i>
                            Email Support
                        </a>
                        <a href="tel:+1234567890" class="btn btn-outline-danger btn-sm">
                            <i class="fas fa-phone me-1"></i>
                            Call Support
                        </a>
                        <a href="#" class="btn btn-outline-danger btn-sm" onclick="openLiveChat()">
                            <i class="fas fa-comments me-1"></i>
                            Live Chat
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function retryPayment() {
            // Go back to payment form
            const gateway = '{{ $gateway }}';
            window.location.href = `{{ route('payment.form') }}?gateway=${gateway}`;
        }

        function contactSupport() {
            // Open support contact
            window.open('mailto:support@example.com?subject=Payment Failed - {{ $gateway }}&body=Payment failed for gateway: {{ $gateway }}%0AError: {{ $error ?? 'Unknown error' }}', '_blank');
        }

        function openLiveChat() {
            // Open live chat (implement your live chat solution)
            alert('Live chat feature would be implemented here.');
        }

        // Auto-retry suggestion after 10 seconds
        setTimeout(() => {
            if (confirm('Would you like to try the payment again?')) {
                retryPayment();
            }
        }, 10000);
    </script>
</body>
</html>
