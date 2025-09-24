<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Gateway - {{ ucfirst($gateway) }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .payment-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        .gateway-logo {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: white;
            margin: 0 auto 20px;
        }
        .nagad-logo { background: linear-gradient(135deg, #00A651, #00C851); }
        .bkash-logo { background: linear-gradient(135deg, #E2136E, #F2136E); }
        .binance-logo { background: linear-gradient(135deg, #F3BA2F, #F3BA2F); }
        .form-control:focus {
            border-color: #007bff;
            box-shadow: 0 0 0 0.2rem rgba(0,123,255,.25);
        }
        .btn-payment {
            background: linear-gradient(135deg, #007bff, #0056b3);
            border: none;
            border-radius: 25px;
            padding: 12px 30px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .btn-payment:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,123,255,0.4);
        }
        .loading {
            display: none;
        }
        .loading.show {
            display: inline-block;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="card payment-card mt-5">
                    <div class="card-body p-5">
                        <!-- Gateway Logo -->
                        <div class="gateway-logo {{ $gateway }}-logo">
                            @if($gateway === 'nagad')
                                <i class="fas fa-mobile-alt"></i>
                            @elseif($gateway === 'bkash')
                                <i class="fas fa-wallet"></i>
                            @elseif($gateway === 'binance')
                                <i class="fab fa-bitcoin"></i>
                            @endif
                        </div>

                        <!-- Gateway Title -->
                        <h3 class="text-center mb-4">
                            {{ ucfirst($gateway) }} Payment
                        </h3>

                        <!-- Payment Form -->
                        <form id="paymentForm" method="POST" action="{{ route('payment.initialize') }}">
                            @csrf
                            <input type="hidden" name="gateway" value="{{ $gateway }}">

                            <!-- Amount -->
                            <div class="mb-3">
                                <label for="amount" class="form-label">
                                    <i class="fas fa-dollar-sign me-2"></i>Amount
                                </label>
                                <div class="input-group">
                                    <input type="number" 
                                           class="form-control" 
                                           id="amount" 
                                           name="amount" 
                                           value="{{ $amount ?? '' }}"
                                           step="0.01" 
                                           min="0.01" 
                                           required>
                                    <span class="input-group-text">{{ $currency ?? 'BDT' }}</span>
                                </div>
                            </div>

                            <!-- Order ID -->
                            <div class="mb-3">
                                <label for="order_id" class="form-label">
                                    <i class="fas fa-receipt me-2"></i>Order ID
                                </label>
                                <input type="text" 
                                       class="form-control" 
                                       id="order_id" 
                                       name="order_id" 
                                       value="{{ $orderId ?? '' }}"
                                       maxlength="50" 
                                       required>
                            </div>

                            <!-- Currency (for Binance) -->
                            @if($gateway === 'binance')
                            <div class="mb-3">
                                <label for="currency" class="form-label">
                                    <i class="fas fa-coins me-2"></i>Currency
                                </label>
                                <select class="form-select" id="currency" name="currency">
                                    <option value="USDT" {{ ($currency ?? 'USDT') === 'USDT' ? 'selected' : '' }}>USDT</option>
                                    <option value="BTC" {{ ($currency ?? '') === 'BTC' ? 'selected' : '' }}>BTC</option>
                                    <option value="ETH" {{ ($currency ?? '') === 'ETH' ? 'selected' : '' }}>ETH</option>
                                    <option value="BNB" {{ ($currency ?? '') === 'BNB' ? 'selected' : '' }}>BNB</option>
                                </select>
                            </div>
                            @endif

                            <!-- Product Name (for Binance) -->
                            @if($gateway === 'binance')
                            <div class="mb-3">
                                <label for="product_name" class="form-label">
                                    <i class="fas fa-tag me-2"></i>Product Name
                                </label>
                                <input type="text" 
                                       class="form-control" 
                                       id="product_name" 
                                       name="product_name" 
                                       value="Payment"
                                       maxlength="255">
                            </div>
                            @endif

                            <!-- Callback URL -->
                            <div class="mb-3">
                                <label for="callback_url" class="form-label">
                                    <i class="fas fa-link me-2"></i>Callback URL
                                </label>
                                <input type="url" 
                                       class="form-control" 
                                       id="callback_url" 
                                       name="callback_url" 
                                       placeholder="https://yourdomain.com/callback">
                            </div>

                            <!-- Submit Button -->
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-payment" id="submitBtn">
                                    <span class="loading" id="loading">
                                        <i class="fas fa-spinner fa-spin me-2"></i>
                                    </span>
                                    <span id="buttonText">
                                        <i class="fas fa-credit-card me-2"></i>
                                        Initialize Payment
                                    </span>
                                </button>
                            </div>
                        </form>

                        <!-- Payment Info -->
                        <div class="mt-4">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                <strong>Payment Information:</strong>
                                <ul class="mb-0 mt-2">
                                    <li>Secure payment processing</li>
                                    <li>Real-time transaction verification</li>
                                    <li>24/7 customer support</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Supported Gateways -->
                <div class="text-center mt-4">
                    <p class="text-muted">Supported Payment Gateways:</p>
                    <div class="d-flex justify-content-center gap-3">
                        @foreach($supportedGateways as $name => $description)
                        <span class="badge bg-secondary">{{ ucfirst($name) }}</span>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('paymentForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const submitBtn = document.getElementById('submitBtn');
            const loading = document.getElementById('loading');
            const buttonText = document.getElementById('buttonText');
            
            // Show loading state
            loading.classList.add('show');
            buttonText.textContent = 'Processing...';
            submitBtn.disabled = true;
            
            // Submit form via AJAX
            fetch(this.action, {
                method: 'POST',
                body: new FormData(this),
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || document.querySelector('input[name="_token"]').value
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (data.redirect_url) {
                        // Redirect to payment gateway
                        window.location.href = data.redirect_url;
                    } else {
                        // Show success message
                        alert('Payment initialized successfully! Payment ID: ' + data.payment_id);
                    }
                } else {
                    // Show error message
                    alert('Payment initialization failed: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while processing your payment. Please try again.');
            })
            .finally(() => {
                // Reset button state
                loading.classList.remove('show');
                buttonText.innerHTML = '<i class="fas fa-credit-card me-2"></i>Initialize Payment';
                submitBtn.disabled = false;
            });
        });

        // Add CSRF token to meta tag if not present
        if (!document.querySelector('meta[name="csrf-token"]')) {
            const meta = document.createElement('meta');
            meta.name = 'csrf-token';
            meta.content = document.querySelector('input[name="_token"]').value;
            document.head.appendChild(meta);
        }
    </script>
</body>
</html>
