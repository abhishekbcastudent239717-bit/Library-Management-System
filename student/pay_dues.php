<?php
session_start();
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

check_student_login();

// Ensure payments table exists
ensure_payments_table_exists();

$error = '';
$success = '';
$student_id = $_SESSION['student_id'];
$payment_id = null;

// Handle payment submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'pay') {
    $amount = sanitize_input($_POST['amount']);
    $payment_method = sanitize_input($_POST['payment_method']);
    $card_number = isset($_POST['card_number']) ? sanitize_input($_POST['card_number']) : '';
    $card_name = isset($_POST['card_name']) ? sanitize_input($_POST['card_name']) : '';
    $expiry = isset($_POST['expiry']) ? sanitize_input($_POST['expiry']) : '';
    $cvv = isset($_POST['cvv']) ? sanitize_input($_POST['cvv']) : '';
    
    $errors = [];
    
    if (empty($amount) || $amount <= 0) {
        $errors[] = "Please enter a valid amount";
    }
    
    if (empty($payment_method)) {
        $errors[] = "Please select a payment method";
    }
    
    // Validate card details if payment method is card
    if ($payment_method == 'card') {
        if (empty($card_number) || strlen(str_replace(' ', '', $card_number)) != 16) {
            $errors[] = "Invalid card number (16 digits required)";
        }
        if (empty($card_name)) {
            $errors[] = "Cardholder name is required";
        }
        if (empty($expiry) || !preg_match('/^\d{2}\/\d{2}$/', $expiry)) {
            $errors[] = "Invalid expiry date (MM/YY format)";
        }
        if (empty($cvv) || strlen($cvv) != 3 || !is_numeric($cvv)) {
            $errors[] = "Invalid CVV (3 digits required)";
        }
    }
    
    if (empty($errors)) {
        // Generate transaction ID
        $transaction_id = 'LIB-' . date('Ymd') . '-' . str_pad($student_id, 4, '0', STR_PAD_LEFT) . '-' . rand(100, 999);
        
        // Insert payment record with 'completed' status for demo
        $query = "INSERT INTO payments (student_id, amount, payment_method, transaction_id, status, payment_date)
                  VALUES ('$student_id', '$amount', '$payment_method', '$transaction_id', 'completed', NOW())";
        
        if (mysqli_query($conn, $query)) {
            $payment_id = mysqli_insert_id($conn);
            
            // Clear student dues by updating fine_amount to 0
            $update_fine = "UPDATE issued_books SET fine_amount = 0 WHERE student_id = '$student_id' AND (status = 'issued' OR status = 'overdue')";
            mysqli_query($conn, $update_fine);
            
            $success = "Payment of ₹" . number_format($amount, 2) . " received successfully! Transaction ID: " . $transaction_id;
            $_POST = array();
        } else {
            $errors[] = "Error processing payment: " . mysqli_error($conn);
            $error = !empty($errors) ? implode("<br>", $errors) : "Error processing payment";
        }
    } else {
        $error = implode("<br>", $errors);
    }
}

// Fetch student's total dues
$query = "SELECT COALESCE(SUM(fine_amount), 0) as total_dues FROM issued_books 
          WHERE student_id = '$student_id' AND (status = 'issued' OR status = 'overdue')";
$dues_result = mysqli_query($conn, $query);
$dues_data = mysqli_fetch_assoc($dues_result);
$total_dues = $dues_data['total_dues'];

// Fetch recent payments
$query = "SELECT * FROM payments WHERE student_id = '$student_id' ORDER BY payment_date DESC LIMIT 10";
$payments_result = mysqli_query($conn, $query);

// Fetch overdue books with fines
$query = "SELECT ib.*, b.title, b.author, DATEDIFF(NOW(), ib.return_date) as overdue_days
          FROM issued_books ib
          JOIN books b ON ib.book_id = b.id
          WHERE ib.student_id = '$student_id' AND (ib.status = 'issued' OR ib.status = 'overdue')
          ORDER BY ib.return_date ASC";
$books_result = mysqli_query($conn, $query);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pay Dues - Library Management System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body>
    <div class="header">
        <h1>📚 Library Management System</h1>
        <div class="user-info">
            <button class="hamburger" onclick="toggleSidebar()">
                <span></span>
                <span></span>
                <span></span>
            </button>
            <span class="user-name">Welcome, <?php echo $_SESSION['student_name']; ?></span>
            <button class="profile-btn" onclick="location.href='dashboard.php';" title="View your profile">
                <span class="profile-icon">👤</span>
                Profile
            </button>
        </div>
    </div>

    <!-- Sidebar Navigation -->
    <div class="sidebar">
        <div class="nav-menu">
            <a href="dashboard.php">📊 Dashboard</a>
            <a href="my_books.php">📚 My Books</a>
            <a href="available_books.php">📖 Available Books</a>
            <a href="announcements.php">📢 Announcements</a>
            <a href="pay_dues.php" class="active">💳 Pay Dues</a>
            <a href="contact.php">📞 Contact Library</a>
        </div>
        <div class="sidebar-logout">
            <a href="logout.php">Logout</a>
        </div>
    </div>

    <div class="content-wrapper">
        <div class="dashboard">
            <h2 style="margin-bottom: 20px;">💳 Pay Your Dues</h2>

            <?php if ($success): ?>
                <div class="success-message" style="margin-bottom: 20px;">
                    ✅ <?php echo $success; ?>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="error-message" style="margin-bottom: 20px;">
                    ❌ <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <!-- Outstanding Dues Card -->
            <div class="card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; margin-bottom: 30px;">
                <div style="padding: 30px; text-align: center;">
                    <p style="font-size: 14px; opacity: 0.9; margin-bottom: 10px;">Outstanding Dues</p>
                    <h2 style="font-size: 48px; font-weight: bold; margin: 0;">₹<?php echo number_format($total_dues, 2); ?></h2>
                    <p style="font-size: 12px; opacity: 0.8; margin-top: 10px;">
                        You have <?php 
                            $overdue_count = 0;
                            mysqli_data_seek($books_result, 0);
                            while ($book = mysqli_fetch_assoc($books_result)) {
                                if ($book['overdue_days'] > 0) $overdue_count++;
                            }
                            echo $overdue_count . ' ' . ($overdue_count == 1 ? 'overdue book' : 'overdue books');
                        ?>
                    </p>
                </div>
            </div>

            <!-- Payment Form -->
            <?php if ($total_dues > 0): ?>
                <div class="card" style="margin-bottom: 30px;">
                    <div class="card-header">
                        <h3>� Secure Payment</h3>
                    </div>
                    <form method="POST" action="" style="padding: 20px;" id="paymentForm">
                        <!-- Amount Section -->
                        <div style="margin-bottom: 30px;">
                            <div class="form-group">
                                <label for="amount">Amount to Pay (₹) *</label>
                                <input type="number" id="amount" name="amount" step="0.01" min="1" max="<?php echo $total_dues; ?>" 
                                       placeholder="Enter amount" value="<?php echo isset($_POST['amount']) ? $_POST['amount'] : $total_dues; ?>" required>
                                <small style="color: #718096;">Total Due: ₹<?php echo number_format($total_dues, 2); ?></small>
                            </div>
                        </div>

                        <!-- Payment Methods -->
                        <h4 style="margin-bottom: 15px; color: #2d3748;">Select Payment Method</h4>
                        <div class="payment-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; margin-bottom: 30px;">
                            <label class="payment-method active" id="cardMethod" style="border: 2px solid #e0e0e0; border-radius: 12px; padding: 15px; cursor: pointer; transition: all 0.3s ease; text-align: center;">
                                <input type="radio" name="payment_method" value="card" checked style="display: none;">
                                <div style="font-size: 2.5rem; margin-bottom: 10px; color: #667eea;">💳</div>
                                <strong style="color: #2d3748; display: block;">Card</strong>
                                <small style="color: #718096;">Credit/Debit</small>
                            </label>

                            <label class="payment-method" id="upiMethod" style="border: 2px solid #e0e0e0; border-radius: 12px; padding: 15px; cursor: pointer; transition: all 0.3s ease; text-align: center;">
                                <input type="radio" name="payment_method" value="upi" style="display: none;">
                                <div style="font-size: 2.5rem; margin-bottom: 10px; color: #667eea;">📱</div>
                                <strong style="color: #2d3748; display: block;">UPI</strong>
                                <small style="color: #718096;">Quick Pay</small>
                            </label>

                            <label class="payment-method" id="bankMethod" style="border: 2px solid #e0e0e0; border-radius: 12px; padding: 15px; cursor: pointer; transition: all 0.3s ease; text-align: center;">
                                <input type="radio" name="payment_method" value="bank transfer" style="display: none;">
                                <div style="font-size: 2.5rem; margin-bottom: 10px; color: #667eea;">🏦</div>
                                <strong style="color: #2d3748; display: block;">Bank</strong>
                                <small style="color: #718096;">Transfer</small>
                            </label>
                        </div>

                        <!-- Card Payment Form -->
                        <div id="cardFormSection" style="display: block;">
                            <h4 style="margin-bottom: 15px; color: #2d3748;">Card Details</h4>

                            <!-- Card Preview -->
                            <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 16px; padding: 30px; color: white; margin-bottom: 30px; position: relative; box-shadow: 0 10px 30px rgba(0,0,0,0.2);">
                                <div style="width: 50px; height: 40px; background: linear-gradient(135deg, #ffd700, #ffed4e); border-radius: 8px; margin-bottom: 30px;"></div>
                                <div id="displayCardNumber" style="font-size: 1.5rem; letter-spacing: 3px; margin-bottom: 20px; font-family: 'Courier New', monospace;">**** **** **** ****</div>
                                <div style="display: flex; justify-content: space-between;">
                                    <div style="font-size: 0.9rem;">
                                        <div style="font-size: 0.7rem; opacity: 0.8; display: block; margin-bottom: 5px;">CARDHOLDER NAME</div>
                                        <span id="displayCardName">YOUR NAME</span>
                                    </div>
                                    <div style="font-size: 0.9rem;">
                                        <div style="font-size: 0.7rem; opacity: 0.8; display: block; margin-bottom: 5px;">EXPIRES</div>
                                        <span id="displayExpiry">MM/YY</span>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="card_number">Card Number *</label>
                                <input type="text" id="card_number" name="card_number" 
                                       placeholder="1234 5678 9012 3456" 
                                       maxlength="19" style="font-family: 'Courier New', monospace;">
                            </div>

                            <div class="form-group">
                                <label for="card_name">Cardholder Name *</label>
                                <input type="text" id="card_name" name="card_name" 
                                       placeholder="Name as on card" 
                                       style="text-transform: uppercase;">
                            </div>

                            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 15px;">
                                <div class="form-group">
                                    <label for="expiry">Expiry Date *</label>
                                    <input type="text" id="expiry" name="expiry" 
                                           placeholder="MM/YY" maxlength="5">
                                </div>
                                <div class="form-group">
                                    <label for="cvv">CVV *</label>
                                    <input type="text" id="cvv" name="cvv" 
                                           placeholder="123" maxlength="3">
                                </div>
                            </div>

                            <div style="display: flex; align-items: center; gap: 8px; color: #27ae60; margin-top: 15px; font-size: 0.9rem;">
                                <span>🔒</span>
                                <span>Your payment information is encrypted and secure</span>
                            </div>
                        </div>

                        <!-- Other Methods Info -->
                        <div id="upiInfo" style="display: none; background: #f7fafc; padding: 20px; border-radius: 8px; border-left: 4px solid #667eea; margin-bottom: 20px;">
                            <strong style="color: #2d3748;">📱 UPI Payment:</strong>
                            <p style="margin-top: 8px; color: #4a5568; font-size: 14px;">
                                After clicking pay, you'll be redirected to your UPI app to complete the payment securely.
                            </p>
                        </div>

                        <div id="bankInfo" style="display: none; background: #f7fafc; padding: 20px; border-radius: 8px; border-left: 4px solid #667eea; margin-bottom: 20px;">
                            <strong style="color: #2d3748;">🏦 Bank Transfer:</strong>
                            <p style="margin-top: 8px; color: #4a5568; font-size: 14px;">
                                Our bank details will be provided after clicking pay. Please mention your reference number in the transaction description.
                            </p>
                        </div>


                        <div style="display: flex; gap: 10px; margin-top: 30px;">
                            <button type="submit" name="action" value="pay" class="btn" style="flex: 1; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 15px; font-weight: bold;">
                                ✓ Pay ₹<span id="payAmount"><?php echo $total_dues; ?></span>
                            </button>
                            <a href="dashboard.php" class="btn btn-secondary" style="flex: 1; text-decoration: none; text-align: center; padding: 15px;">Cancel</a>
                        </div>
                    </form>
                </div>
            <?php else: ?>
                <div class="card" style="text-align: center; padding: 40px;">
                    <h3 style="color: #2d3748; margin-bottom: 10px;">✅ No Outstanding Dues</h3>
                    <p style="color: #4a5568;">You have no pending fines. Great job!</p>
                </div>
            <?php endif; ?>

            <!-- Overdue Books Table -->
            <?php if (mysqli_num_rows($books_result) > 0): ?>
                <div class="card" style="margin-top: 30px;">
                    <div class="card-header">
                        <h3>📖 Overdue Books</h3>
                    </div>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Book Title</th>
                                    <th>Author</th>
                                    <th>Return Date</th>
                                    <th>Days Overdue</th>
                                    <th>Fine (₹5/day)</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                mysqli_data_seek($books_result, 0);
                                while ($book = mysqli_fetch_assoc($books_result)): 
                                ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($book['title']); ?></td>
                                        <td><?php echo htmlspecialchars($book['author']); ?></td>
                                        <td><?php echo format_date($book['return_date']); ?></td>
                                        <td>
                                            <?php 
                                            $overdue = $book['overdue_days'];
                                            if ($overdue > 0) {
                                                echo '<span class="badge badge-danger">' . $overdue . ' days</span>';
                                            } else {
                                                echo '-';
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <?php 
                                            if ($book['fine_amount'] > 0) {
                                                echo '<span class="badge badge-warning">₹' . number_format($book['fine_amount'], 2) . '</span>';
                                            } else {
                                                echo '-';
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <?php 
                                            if ($book['status'] == 'overdue') {
                                                echo '<span class="badge badge-danger">Overdue</span>';
                                            } else {
                                                echo '<span class="badge badge-info">Issued</span>';
                                            }
                                            ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Payment History -->
            <?php if (mysqli_num_rows($payments_result) > 0): ?>
                <div class="card" style="margin-top: 30px;">
                    <div class="card-header">
                        <h3>📜 Payment History</h3>
                    </div>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Receipt #</th>
                                    <th>Amount</th>
                                    <th>Method</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($payment = mysqli_fetch_assoc($payments_result)): ?>
                                    <tr>
                                        <td>#<?php echo str_pad($payment['id'], 6, '0', STR_PAD_LEFT); ?></td>
                                        <td><strong>₹<?php echo number_format($payment['amount'], 2); ?></strong></td>
                                        <td><?php echo ucfirst($payment['payment_method']); ?></td>
                                        <td>
                                            <?php 
                                            $status_class = $payment['status'] == 'completed' ? 'badge-success' : 'badge-warning';
                                            echo '<span class="badge ' . $status_class . '">' . ucfirst($payment['status']) . '</span>';
                                            ?>
                                        </td>
                                        <td><?php echo date('d M Y, H:i A', strtotime($payment['payment_date'])); ?></td>
                                        <td>
                                            <button class="btn btn-small" onclick="viewPaymentReceipt(<?php echo $payment['id']; ?>)">📄 PDF</button>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="../assets/js/script.js"></script>
    <script>
        // Payment Method Selection Handler
        const cardMethod = document.getElementById('cardMethod');
        const upiMethod = document.getElementById('upiMethod');
        const bankMethod = document.getElementById('bankMethod');

        const cardFormSection = document.getElementById('cardFormSection');
        const upiInfo = document.getElementById('upiInfo');
        const bankInfo = document.getElementById('bankInfo');

        const cardInputs = cardFormSection.querySelectorAll('input[name^="card_"], input[name="expiry"], input[name="cvv"]');

        function setPaymentMethod(method) {
            // Hide all sections
            cardFormSection.style.display = 'none';
            upiInfo.style.display = 'none';
            bankInfo.style.display = 'none';

            // Remove active class from all
            document.querySelectorAll('.payment-method').forEach(el => {
                el.style.borderColor = '#e0e0e0';
                el.style.backgroundColor = 'transparent';
            });

            // Set required attributes
            cardInputs.forEach(input => input.required = false);

            // Show relevant section and mark active
            if (method === 'card') {
                cardFormSection.style.display = 'block';
                cardMethod.style.borderColor = '#667eea';
                cardMethod.style.backgroundColor = '#f8f9ff';
                cardInputs.forEach(input => input.required = true);
            } else if (method === 'upi') {
                upiInfo.style.display = 'block';
                upiMethod.style.borderColor = '#667eea';
                upiMethod.style.backgroundColor = '#f8f9ff';
            } else if (method === 'bank transfer') {
                bankInfo.style.display = 'block';
                bankMethod.style.borderColor = '#667eea';
                bankMethod.style.backgroundColor = '#f8f9ff';
            }
        }

        cardMethod.addEventListener('click', function() { setPaymentMethod('card'); });
        upiMethod.addEventListener('click', function() { setPaymentMethod('upi'); });
        bankMethod.addEventListener('click', function() { setPaymentMethod('bank transfer'); });

        // Card Input Handlers
        const cardNumber = document.getElementById('card_number');
        const cardName = document.getElementById('card_name');
        const expiry = document.getElementById('expiry');
        const cvv = document.getElementById('cvv');

        const displayCardNumber = document.getElementById('displayCardNumber');
        const displayCardName = document.getElementById('displayCardName');
        const displayExpiry = document.getElementById('displayExpiry');

        // Update payment amount display
        document.getElementById('amount').addEventListener('input', function() {
            document.getElementById('payAmount').textContent = this.value || '<?php echo $total_dues; ?>';
        });

        // Card number formatting and display
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

        // Cardholder name
        cardName.addEventListener('input', function(e) {
            displayCardName.textContent = e.target.value.toUpperCase() || 'YOUR NAME';
        });

        // Expiry date formatting
        expiry.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length >= 2) {
                e.target.value = value.slice(0, 2) + '/' + value.slice(2, 4);
            } else {
                e.target.value = value;
            }
            displayExpiry.textContent = e.target.value || 'MM/YY';
        });

        // CVV - only numbers
        cvv.addEventListener('input', function(e) {
            e.target.value = e.target.value.replace(/\D/g, '');
        });

        // Initialize with card method
        setPaymentMethod('card');

        function viewPaymentReceipt(paymentId) {
            // Open payment receipt in new window for printing
            window.open('view_receipt.php?payment_id=' + paymentId, '_blank', 'width=900,height=700');
        }
    </script>
</body>

</html>
