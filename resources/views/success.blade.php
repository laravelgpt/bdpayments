<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Successful - {{ ucfirst($gateway) }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .success-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        .success-icon {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, #28a745, #20c997);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
            color: white;
            margin: 0 auto 30px;
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        .gateway-badge {
            font-size: 14px;
            padding: 8px 16px;
            border-radius: 20px;
        }
        .nagad-badge { background: linear-gradient(135deg, #00A651, #00C851); color: white; }
        .bkash-badge { background: linear-gradient(135deg, #E2136E, #F2136E); color: white; }
        .binance-badge { background: linear-gradient(135deg, #F3BA2F, #F3BA2F); color: white; }
    </style>
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="card success-card mt-5">
                    <div class="card-body p-5 text-center">
                        <!-- Success Icon -->
                        <div class="success-icon">
                            <i class="fas fa-check"></i>
                        </div>

                        <!-- Success Message -->
                        <h2 class="text-success mb-3">Payment Successful!</h2>
                        <p class="text-muted mb-4">Your payment has been processed successfully.</p>

                        <!-- Gateway Badge -->
                        <div class="mb-4">
                            <span class="gateway-badge {{ $gateway }}-badge">
                                <i class="fas fa-credit-card me-2"></i>
                                {{ ucfirst($gateway) }} Payment
                            </span>
                        </div>

                        <!-- Payment Details -->
                        <div class="card bg-light mb-4">
                            <div class="card-body">
                                <h5 class="card-title">
                                    <i class="fas fa-receipt me-2"></i>
                                    Payment Details
                                </h5>
                                <div class="row text-start">
                                    <div class="col-6">
                                        <strong>Payment ID:</strong>
                                    </div>
                                    <div class="col-6">
                                        <code>{{ $payment_id ?? 'N/A' }}</code>
                                    </div>
                                    <div class="col-6">
                                        <strong>Amount:</strong>
                                    </div>
                                    <div class="col-6">
                                        <strong class="text-success">
                                            {{ number_format($amount ?? 0, 2) }} {{ $currency ?? 'BDT' }}
                                        </strong>
                                    </div>
                                    <div class="col-6">
                                        <strong>Status:</strong>
                                    </div>
                                    <div class="col-6">
                                        <span class="badge bg-success">Completed</span>
                                    </div>
                                    <div class="col-6">
                                        <strong>Date:</strong>
                                    </div>
                                    <div class="col-6">
                                        {{ now()->format('M d, Y H:i:s') }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="d-grid gap-2">
                            <button class="btn btn-primary" onclick="window.print()">
                                <i class="fas fa-print me-2"></i>
                                Print Receipt
                            </button>
                            <button class="btn btn-outline-primary" onclick="window.close()">
                                <i class="fas fa-times me-2"></i>
                                Close Window
                            </button>
                        </div>

                        <!-- Additional Info -->
                        <div class="mt-4">
                            <div class="alert alert-success">
                                <i class="fas fa-info-circle me-2"></i>
                                <strong>What's Next?</strong>
                                <ul class="mb-0 mt-2 text-start">
                                    <li>You will receive a confirmation email shortly</li>
                                    <li>Your order will be processed within 24 hours</li>
                                    <li>Contact support if you have any questions</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Support Information -->
                <div class="text-center mt-4">
                    <p class="text-muted">
                        <i class="fas fa-headset me-2"></i>
                        Need help? Contact our support team
                    </p>
                    <div class="d-flex justify-content-center gap-3">
                        <a href="mailto:support@example.com" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-envelope me-1"></i>
                            Email Support
                        </a>
                        <a href="tel:+1234567890" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-phone me-1"></i>
                            Call Support
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-close after 30 seconds if opened in popup
        if (window.opener) {
            setTimeout(() => {
                if (confirm('Close this window?')) {
                    window.close();
                }
            }, 30000);
        }

        // Print receipt function
        function printReceipt() {
            window.print();
        }
    </script>
</body>
</html>
