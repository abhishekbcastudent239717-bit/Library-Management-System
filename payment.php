<?php
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

if (!isset($_SESSION['pending_booking'])) {
    header('Location: rooms.php');
    exit();
}

$booking_data = $_SESSION['pending_booking'];
$conn = getDBConnection();
$user_id = $_SESSION['user_id'];

$user_sql = "SELECT * FROM users WHERE id = $user_id";
$user_result = mysqli_query($conn, $user_sql);
$user = mysqli_fetch_assoc($user_result);

$room_sql = "SELECT * FROM rooms WHERE room_id = " . $booking_data['room_id'];
$room_result = mysqli_query($conn, $room_sql);
$room = mysqli_fetch_assoc($room_result);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $payment_method = $_POST['payment_method'];
    $card_number = isset($_POST['card_number']) ? $_POST['card_number'] : '';
    $card_name = isset($_POST['card_name']) ? $_POST['card_name'] : '';
    $expiry = isset($_POST['expiry']) ? $_POST['expiry'] : '';
    $cvv = isset($_POST['cvv']) ? $_POST['cvv'] : '';

    $errors = [];

    if ($payment_method == 'card') {
        if (empty($card_number) || strlen(str_replace(' ', '', $card_number)) != 16) {
            $errors[] = "Invalid card number";
        }
        if (empty($card_name)) {
            $errors[] = "Cardholder name is required";
        }
        if (empty($expiry)) {
            $errors[] = "Expiry date is required";
        }
        if (empty($cvv) || strlen($cvv) != 3) {
            $errors[] = "Invalid CVV";
        }
    }

    if (empty($errors)) {

        $transaction_id = 'TXN' . strtoupper(uniqid());

        $insert_sql = "INSERT INTO bookings (user_id, room_id, checkin_date, checkout_date, total_amount, status, payment_method, transaction_id) 
                      VALUES ($user_id, {$booking_data['room_id']}, '{$booking_data['checkin_date']}', '{$booking_data['checkout_date']}', {$booking_data['total_amount']}, 'confirmed', '$payment_method', '$transaction_id')";

        if (mysqli_query($conn, $insert_sql)) {
            $booking_id = mysqli_insert_id($conn);
            unset($_SESSION['pending_booking']);
            $_SESSION['success_message'] = "Payment successful! Booking confirmed.";
            header('Location: booking_confirmation.php?booking_id=' . $booking_id);
            exit();
        } else {
            $errors[] = "Payment processed but booking creation failed. Please contact support.";
        }
    }
}

mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment - Paradise Hotel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .payment-container {
            max-width: 900px;
            margin: 0 auto;
        }
        .payment-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin-top: 2rem;
        }
        .payment-method {
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            padding: 1.5rem;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
        }
        .payment-method:hover {
            border-color: #667eea;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.2);
        }
        .payment-method.active {
            border-color: #667eea;
            background: #f8f9ff;
        }
        .payment-method input[type="radio"] {
            display: none;
        }
        .payment-method i {
            font-size: 3rem;
            color: #667eea;
            margin-bottom: 1rem;
        }
        .payment-method h3 {
            color: #2d3748;
            margin-bottom: 0.5rem;
        }
        .payment-method p {
            color: #718096;
            font-size: 0.9rem;
        }
        .card-form {
            display: none;
            margin-top: 2rem;
        }
        .card-form.active {
            display: block;
        }
        .card-preview {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 16px;
            padding: 2rem;
            color: white;
            margin-bottom: 2rem;
            min-height: 200px;
            position: relative;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        .card-chip {
            width: 50px;
            height: 40px;
            background: linear-gradient(135deg, #ffd700, #ffed4e);
            border-radius: 8px;
            margin-bottom: 2rem;
        }
        .card-number {
            font-size: 1.5rem;
            letter-spacing: 3px;
            margin-bottom: 1.5rem;
            font-family: 'Courier New', monospace;
        }
        .card-details {
            display: flex;
            justify-content: space-between;
        }
        .card-holder, .card-expiry {
            font-size: 0.9rem;
        }
        .card-label {
            font-size: 0.7rem;
            opacity: 0.8;
            display: block;
            margin-bottom: 0.3rem;
        }
        .form-group-inline {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 1rem;
        }
        .booking-summary-box {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 1.5rem;
            margin-top: 2rem;
        }
        .summary-item {
            display: flex;
            justify-content: space-between;
            padding: 0.8rem 0;
            border-bottom: 1px solid #e0e0e0;
        }
        .summary-item:last-child {
            border-bottom: none;
            font-weight: bold;
            font-size: 1.2rem;
            color: #667eea;
        }
        .secure-badge {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #27ae60;
            margin-top: 1rem;
            font-size: 0.9rem;
        }
        @media (max-width: 768px) {
            .payment-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="main-container">
        <main class="main-content" style="max-width: 1000px; margin: 0 auto; padding: 2rem;">
            <div class="payment-container">
                <div class="content-header">
                    <h1><i class="fas fa-credit-card"></i> Complete Payment</h1>
                    <p>Secure payment processing for your booking</p>
                </div>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-error">
                        <?php foreach($errors as $error): ?>
                            <p><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></p>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <!-- Booking Summary -->
                <div class="section-card">
                    <h2>Booking Summary</h2>
                    <div class="booking-summary-box">
                        <div class="summary-item">
                            <span>Room Type:</span>
                            <strong><?php echo htmlspecialchars($room['room_type']); ?></strong>
                        </div>
                        <div class="summary-item">
                            <span>Check-in:</span>
                            <strong><?php echo date('d M Y', strtotime($booking_data['checkin_date'])); ?></strong>
                        </div>
                        <div class="summary-item">
                            <span>Check-out:</span>
                            <strong><?php echo date('d M Y', strtotime($booking_data['checkout_date'])); ?></strong>
                        </div>
                        <div class="summary-item">
                            <span>Number of Nights:</span>
                            <strong><?php echo $booking_data['nights']; ?></strong>
                        </div>
                        <div class="summary-item">
                            <span>Total Amount:</span>
                            <strong>₹<?php echo number_format($booking_data['total_amount'], 2); ?></strong>
                        </div>
                    </div>
                </div>

                <!-- Payment Method Selection -->
                <div class="section-card">
                    <h2>Select Payment Method</h2>
                    <form method="POST" action="" id="paymentForm">
                        <div class="payment-grid">
                            <label class="payment-method active" id="cardMethod">
                                <input type="radio" name="payment_method" value="card" checked>
                                <i class="fas fa-credit-card"></i>
                                <h3>Credit/Debit Card</h3>
                                <p>Pay securely with your card</p>
                            </label>

                            <label class="payment-method" id="cashMethod">
                                <input type="radio" name="payment_method" value="cash">
                                <i class="fas fa-money-bill-wave"></i>
                                <h3>Pay at Hotel</h3>
                                <p>Pay during check-in</p>
                            </label>
                        </div>

                        <!-- Card Payment Form -->
                        <div class="card-form active" id="cardFormSection">
                            <h3 style="margin-bottom: 1.5rem;">Card Details</h3>

                            <div class="card-preview">
                                <div class="card-chip"></div>
                                <div class="card-number" id="displayCardNumber">**** **** **** ****</div>
                                <div class="card-details">
                                    <div class="card-holder">
                                        <span class="card-label">CARDHOLDER NAME</span>
                                        <span id="displayCardName">YOUR NAME</span>
                                    </div>
                                    <div class="card-expiry">
                                        <span class="card-label">EXPIRES</span>
                                        <span id="displayExpiry">MM/YY</span>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Card Number *</label>
                                <input type="text" name="card_number" id="cardNumber" 
                                       placeholder="1234 5678 9012 3456" 
                                       maxlength="19" required>
                            </div>

                            <div class="form-group">
                                <label>Cardholder Name *</label>
                                <input type="text" name="card_name" id="cardName" 
                                       placeholder="Name as on card" 
                                       style="text-transform: uppercase;" required>
                            </div>

                            <div class="form-group-inline">
                                <div class="form-group">
                                    <label>Expiry Date *</label>
                                    <input type="text" name="expiry" id="expiry" 
                                           placeholder="MM/YY" maxlength="5" required>
                                </div>
                                <div class="form-group">
                                    <label>CVV *</label>
                                    <input type="password" name="cvv" id="cvv" 
                                           placeholder="123" maxlength="3" required>
                                </div>
                            </div>

                            <div class="secure-badge">
                                <i class="fas fa-lock"></i>
                                <span>Your payment information is encrypted and secure</span>
                            </div>
                        </div>

                        <button type="submit" class="btn-primary btn-block" style="margin-top: 2rem;">
                            <i class="fas fa-shield-alt"></i> Pay ₹<?php echo number_format($booking_data['total_amount'], 2); ?>
                        </button>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <script>

        const cardMethod = document.getElementById('cardMethod');
        const cashMethod = document.getElementById('cashMethod');
        const cardFormSection = document.getElementById('cardFormSection');
        const cardInputs = cardFormSection.querySelectorAll('input[required]');

        cardMethod.addEventListener('click', function() {
            cardMethod.classList.add('active');
            cashMethod.classList.remove('active');
            cardFormSection.classList.add('active');
            cardInputs.forEach(input => input.required = true);
        });

        cashMethod.addEventListener('click', function() {
            cashMethod.classList.add('active');
            cardMethod.classList.remove('active');
            cardFormSection.classList.remove('active');
            cardInputs.forEach(input => input.required = false);
        });

        const cardNumber = document.getElementById('cardNumber');
        const cardName = document.getElementById('cardName');
        const expiry = document.getElementById('expiry');
        const displayCardNumber = document.getElementById('displayCardNumber');
        const displayCardName = document.getElementById('displayCardName');
        const displayExpiry = document.getElementById('displayExpiry');

        cardNumber.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\s/g, '');
            let formattedValue = value.match(/.{1,4}/g)?.join(' ') || '';
            e.target.value = formattedValue;

            if (value.length > 0) {
                let masked = value.replace(/\d(?=\d{4})/g, '*');
                displayCardNumber.textContent = masked.match(/.{1,4}/g)?.join(' ') || '**** **** **** ****';
            } else {
                displayCardNumber.textContent = '**** **** **** ****';
            }
        });

        cardName.addEventListener('input', function(e) {
            displayCardName.textContent = e.target.value.toUpperCase() || 'YOUR NAME';
        });

        expiry.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length >= 2) {
                e.target.value = value.slice(0, 2) + '/' + value.slice(2, 4);
            } else {
                e.target.value = value;
            }
            displayExpiry.textContent = e.target.value || 'MM/YY';
        });

        document.getElementById('cvv').addEventListener('input', function(e) {
            e.target.value = e.target.value.replace(/\D/g, '');
        });
    </script>
</body>
</html>
