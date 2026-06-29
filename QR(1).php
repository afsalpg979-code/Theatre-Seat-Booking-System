<?php
require_once __DIR__ . '/razorpay_helpers.php';

if (!isset($_GET['id'])) {
    echo "Invalid booking ID.";
    exit;
}

$id = intval($_GET['id']);
$row = getBookingById($conn, $id);

if (!$row) {
    echo "No booking found for this ID.";
    exit;
}

$configReady = razorpayConfigured();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UPI Payment - FALCONS Theater</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="theme.css">
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
</head>
<body class="theme-body">
    <div class="page-shell">
        <section class="page-hero">
            <div class="hero-content">
                <span class="eyebrow">UPI Checkout</span>
                <h1>Open a live payment order with the exact booking amount.</h1>
                <p class="hero-copy">This checkout is prepared for automatic status tracking, success redirect, and mobile-friendly completion once Razorpay keys are configured.</p>
            </div>
        </section>

        <section class="content-grid grid-two">
            <div class="glass-panel panel-padding stack">
                <div class="detail-list">
                    <div class="detail-item"><strong>Name</strong><?= htmlspecialchars($row['name']) ?></div>
                    <div class="detail-item"><strong>Phone</strong><?= htmlspecialchars($row['phone']) ?></div>
                    <div class="detail-item"><strong>Movie</strong><?= htmlspecialchars($row['movie']) ?></div>
                    <div class="detail-item"><strong>Theater</strong><?= htmlspecialchars($row['theater'] ?? 'T1 - 4K') ?></div>
                    <div class="detail-item"><strong>Seats</strong><?= htmlspecialchars($row['seats']) ?></div>
                    <div class="detail-item"><strong>Total Amount</strong>Rs.<?= number_format($row['totalAmount'], 2) ?></div>
                </div>
            </div>

            <div class="glass-panel surface-panel form-card">
                <h2>UPI Payment Flow</h2>
                <?php if ($configReady): ?>
                    <p class="form-copy">Click below to create a secure order, open checkout, and move to the ticket automatically once payment is verified.</p>
                    <div class="action-row">
                        <button id="pay-now-btn" class="btn">Pay Now</button>
                        <a href="buy.php?id=<?= $id ?>" class="btn-outline">Back to Payment Options</a>
                    </div>
                    <div id="payment-status" class="status-banner status-success" style="background: rgba(243,182,31,0.14); color:#7a5c00; border-color: rgba(243,182,31,0.22);">Waiting to start payment.</div>
                <?php else: ?>
                    <div class="status-banner status-error">Razorpay is not configured yet. Add your live or test keys in <strong>razorpay_config.php</strong> to enable automatic payment status checking.</div>
                    <a href="buy.php?id=<?= $id ?>" class="btn-outline">Back to Payment Options</a>
                <?php endif; ?>
            </div>
        </section>
    </div>

    <?php if ($configReady): ?>
        <script>
            const bookingId = <?= $id ?>;
            const payButton = document.getElementById('pay-now-btn');
            const statusBox = document.getElementById('payment-status');
            let statusInterval = null;

            function setStatus(message, type) {
                statusBox.textContent = message;
                statusBox.className = 'status-banner ' + type;
            }

            function startPolling(orderId) {
                if (statusInterval) {
                    clearInterval(statusInterval);
                }

                statusInterval = setInterval(async () => {
                    try {
                        const response = await fetch(`razorpay_payment_status.php?booking_id=${bookingId}&order_id=${encodeURIComponent(orderId)}`);
                        const data = await response.json();

                        if (data.status === 'paid') {
                            clearInterval(statusInterval);
                            setStatus('Payment successful. Opening ticket...', 'status-success');
                            window.location.href = data.redirect_url;
                        } else if (data.status === 'failed') {
                            clearInterval(statusInterval);
                            setStatus(data.message || 'Payment failed.', 'status-error');
                        } else {
                            setStatus('Payment pending. Waiting for confirmation...', 'status-success');
                        }
                    } catch (error) {
                        setStatus('Unable to check payment status right now.', 'status-error');
                    }
                }, 4000);
            }

            async function createOrder() {
                const response = await fetch('razorpay_create_order.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ booking_id: bookingId })
                });

                return response.json();
            }

            async function verifyPayment(payload) {
                const response = await fetch('razorpay_verify_payment.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        booking_id: bookingId,
                        razorpay_order_id: payload.razorpay_order_id,
                        razorpay_payment_id: payload.razorpay_payment_id,
                        razorpay_signature: payload.razorpay_signature
                    })
                });

                return response.json();
            }

            payButton.addEventListener('click', async () => {
                payButton.disabled = true;
                setStatus('Creating secure payment order...', 'status-success');

                try {
                    const orderData = await createOrder();
                    if (!orderData.success) {
                        setStatus(orderData.error || 'Unable to start payment.', 'status-error');
                        payButton.disabled = false;
                        return;
                    }

                    startPolling(orderData.order_id);

                    const options = {
                        key: orderData.key_id,
                        amount: orderData.amount,
                        currency: orderData.currency,
                        name: orderData.name,
                        description: orderData.description,
                        order_id: orderData.order_id,
                        prefill: {
                            name: orderData.customer_name,
                            contact: orderData.customer_phone
                        },
                        theme: {
                            color: orderData.theme_color
                        },
                        handler: async function (response) {
                            setStatus('Verifying payment...', 'status-success');
                            const verifyData = await verifyPayment(response);
                            if (verifyData.success) {
                                setStatus('Payment successful. Opening ticket...', 'status-success');
                                window.location.href = verifyData.redirect_url;
                            } else {
                                setStatus(verifyData.error || 'Payment verification failed.', 'status-error');
                                payButton.disabled = false;
                            }
                        },
                        modal: {
                            ondismiss: function () {
                                setStatus('Payment window closed. If you completed payment, waiting for status update...', 'status-success');
                                payButton.disabled = false;
                            }
                        }
                    };

                    const razorpay = new Razorpay(options);
                    razorpay.on('payment.failed', function (response) {
                        setStatus(response.error.description || 'Payment failed.', 'status-error');
                        payButton.disabled = false;
                    });
                    razorpay.open();
                } catch (error) {
                    setStatus('Unable to start payment right now.', 'status-error');
                    payButton.disabled = false;
                }
            });
        </script>
    <?php endif; ?>
</body>
</html>
