<?php
session_start();
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

check_admin_login();

// Ensure payments table exists
ensure_payments_table_exists();

$error = '';
$success = '';

// Handle payment status update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'update_status') {
    $payment_id = sanitize_input($_POST['payment_id']);
    $new_status = sanitize_input($_POST['status']);
    $admin_notes = sanitize_input($_POST['notes']);
    
    $allowed_statuses = ['pending', 'completed', 'failed', 'cancelled'];
    
    if (!in_array($new_status, $allowed_statuses)) {
        $error = "Invalid status";
    } else {
        $query = "UPDATE payments SET status = '$new_status', notes = '$admin_notes' WHERE id = '$payment_id'";
        if (mysqli_query($conn, $query)) {
            $success = "Payment status updated successfully";
        } else {
            $error = "Error updating payment: " . mysqli_error($conn);
        }
    }
}

// Handle payment deletion
if (isset($_GET['delete'])) {
    $payment_id = sanitize_input($_GET['delete']);
    $query = "DELETE FROM payments WHERE id = '$payment_id'";
    if (mysqli_query($conn, $query)) {
        $success = "Payment deleted successfully";
    } else {
        $error = "Error deleting payment: " . mysqli_error($conn);
    }
}

// Fetch filter parameters
$filter_status = isset($_GET['status']) ? sanitize_input($_GET['status']) : '';
$filter_student = isset($_GET['student']) ? sanitize_input($_GET['student']) : '';
$filter_method = isset($_GET['method']) ? sanitize_input($_GET['method']) : '';

// Build query with filters
$where_clause = "1=1";
if ($filter_status) {
    $where_clause .= " AND p.status = '$filter_status'";
}
if ($filter_student) {
    $where_clause .= " AND (s.name LIKE '%$filter_student%' OR s.email LIKE '%$filter_student%')";
}
if ($filter_method) {
    $where_clause .= " AND p.payment_method = '$filter_method'";
}

// Fetch all payments with filters
$query = "SELECT p.*, s.name as student_name, s.email, s.id as student_id
          FROM payments p
          JOIN students s ON p.student_id = s.id
          WHERE $where_clause
          ORDER BY p.payment_date DESC";

$payments_result = mysqli_query($conn, $query);

// Fetch payment statistics
$stats_query = "SELECT 
                    COUNT(*) as total_payments,
                    SUM(CASE WHEN status = 'completed' THEN amount ELSE 0 END) as completed_amount,
                    SUM(CASE WHEN status = 'pending' THEN amount ELSE 0 END) as pending_amount,
                    COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_count,
                    COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_count,
                    COUNT(CASE WHEN status = 'failed' THEN 1 END) as failed_count
                FROM payments";

$stats_result = mysqli_query($conn, $stats_query);
$stats = mysqli_fetch_assoc($stats_result);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Management - Library Management System</title>
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
            <span class="user-name">Welcome, <?php echo $_SESSION['admin_name']; ?> 💳</span>
            <button class="profile-btn" onclick="openProfileModal()" title="View your profile">
                <span class="profile-icon">⚙️</span>
                Profile
            </button>
        </div>
    </div>

    <!-- Sidebar Navigation -->
    <div class="sidebar">
        <div class="nav-menu">
            <a href="dashboard.php">📊 Dashboard</a>
            <a href="books.php">📚 Manage Books</a>
            <a href="students.php">👥 Manage Students</a>
            <a href="categories.php">🗂️ Categories</a>
            <a href="issue_book.php">📤 Issue Book</a>
            <a href="issued_books.php">📋 Issued Books</a>
            <a href="payments.php" class="active">💳 Payments</a>
            <a href="announcements.php">📢 Announcements</a>
            <a href="contact_messages.php">📞 Contact Messages</a>
        </div>
        <div class="sidebar-logout">
            <a href="logout.php">Logout</a>
        </div>
    </div>

    <div class="content-wrapper">
        <div class="dashboard">
            <h2 style="margin-bottom: 20px;">💳 Payment Management</h2>

            <?php if ($success): ?>
                <div class="success-message" style="margin-bottom: 20px;">✅ <?php echo $success; ?></div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="error-message" style="margin-bottom: 20px;">❌ <?php echo $error; ?></div>
            <?php endif; ?>

            <!-- Statistics Cards -->
            <div class="stats-container" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); margin-bottom: 30px;">
                <div class="stat-card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                    <h3>💰 Total Revenue</h3>
                    <div class="stat-number" style="color: white;">₹<?php echo number_format(($stats['completed_amount'] ?? 0) + ($stats['pending_amount'] ?? 0), 0); ?></div>
                    <div class="stat-detail" style="color: rgba(255,255,255,0.8);">All payments</div>
                </div>
                <div class="stat-card green">
                    <h3>✅ Completed</h3>
                    <div class="stat-number">₹<?php echo number_format($stats['completed_amount'] ?? 0, 0); ?></div>
                    <div class="stat-detail"><?php echo $stats['completed_count'] ?? 0; ?> transactions</div>
                </div>
                <div class="stat-card orange">
                    <h3>⏳ Pending</h3>
                    <div class="stat-number">₹<?php echo number_format($stats['pending_amount'] ?? 0, 0); ?></div>
                    <div class="stat-detail"><?php echo $stats['pending_count'] ?? 0; ?> waiting</div>
                </div>
                <div class="stat-card blue">
                    <h3>📊 Total</h3>
                    <div class="stat-number"><?php echo $stats['total_payments'] ?? 0; ?></div>
                    <div class="stat-detail">Transactions</div>
                </div>
                <div class="stat-card red">
                    <h3>❌ Failed</h3>
                    <div class="stat-number"><?php echo $stats['failed_count'] ?? 0; ?></div>
                    <div class="stat-detail">Declined</div>
                </div>
            </div>

            <!-- Filters -->
            <div class="card" style="margin-bottom: 20px;">
                <div class="card-header">
                    <h3>🔍 Filter Payments</h3>
                </div>
                <form method="GET" action="" style="padding: 20px; display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; align-items: end;">
                    <div class="form-group" style="margin: 0;">
                        <label>Student Name/Email</label>
                        <input type="text" name="student" placeholder="Search student..." value="<?php echo htmlspecialchars($filter_student); ?>">
                    </div>
                    <div class="form-group" style="margin: 0;">
                        <label>Status</label>
                        <select name="status">
                            <option value="">All Status</option>
                            <option value="completed" <?php echo $filter_status == 'completed' ? 'selected' : ''; ?>>Completed</option>
                            <option value="pending" <?php echo $filter_status == 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="failed" <?php echo $filter_status == 'failed' ? 'selected' : ''; ?>>Failed</option>
                            <option value="cancelled" <?php echo $filter_status == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                    </div>
                    <div class="form-group" style="margin: 0;">
                        <label>Payment Method</label>
                        <select name="method">
                            <option value="">All Methods</option>
                            <option value="card" <?php echo $filter_method == 'card' ? 'selected' : ''; ?>>💳 Card</option>
                            <option value="upi" <?php echo $filter_method == 'upi' ? 'selected' : ''; ?>>📱 UPI</option>
                            <option value="bank transfer" <?php echo $filter_method == 'bank transfer' ? 'selected' : ''; ?>>🏦 Bank Transfer</option>
                        </select>
                    </div>
                    <button type="submit" class="btn" style="width: 100%; padding: 12px;">Apply Filters</button>
                    <a href="payments.php" class="btn btn-secondary" style="width: 100%; padding: 12px; text-decoration: none; text-align: center;">Clear</a>
                </form>
            </div>

            <!-- Payments Table -->
            <div class="card">
                <div class="card-header">
                    <h3>📊 All Payments (<?php echo mysqli_num_rows($payments_result); ?>)</h3>
                </div>

                <?php if (mysqli_num_rows($payments_result) > 0): ?>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Receipt #</th>
                                    <th>Student Name</th>
                                    <th>Email</th>
                                    <th>Amount</th>
                                    <th>Method</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                    <th>Transaction ID</th>
                                    <th>Notes</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($payment = mysqli_fetch_assoc($payments_result)): ?>
                                    <tr>
                                        <td><strong>#<?php echo str_pad($payment['id'], 6, '0', STR_PAD_LEFT); ?></strong></td>
                                        <td><?php echo htmlspecialchars($payment['student_name']); ?></td>
                                        <td><?php echo htmlspecialchars($payment['email']); ?></td>
                                        <td><strong style="color: #667eea;">₹<?php echo number_format($payment['amount'], 2); ?></strong></td>
                                        <td>
                                            <?php 
                                            $method_icons = [
                                                'card' => '💳 Card',
                                                'upi' => '📱 UPI',
                                                'bank transfer' => '🏦 Bank',
                                                'demo' => '🎯 Demo'
                                            ];
                                            echo isset($method_icons[$payment['payment_method']]) ? $method_icons[$payment['payment_method']] : ucfirst($payment['payment_method']);
                                            ?>
                                        </td>
                                        <td>
                                            <?php 
                                            $status_classes = [
                                                'completed' => 'badge-success',
                                                'pending' => 'badge-warning',
                                                'failed' => 'badge-danger',
                                                'cancelled' => 'badge-secondary'
                                            ];
                                            $status_class = $status_classes[$payment['status']] ?? 'badge-secondary';
                                            $status_icons = [
                                                'completed' => '✅',
                                                'pending' => '⏳',
                                                'failed' => '❌',
                                                'cancelled' => '🚫'
                                            ];
                                            $status_icon = $status_icons[$payment['status']] ?? '❓';
                                            echo '<span class="badge ' . $status_class . '">' . $status_icon . ' ' . ucfirst($payment['status']) . '</span>';
                                            ?>
                                        </td>
                                        <td><?php echo date('d M Y', strtotime($payment['payment_date'])); ?></td>
                                        <td><small><?php echo $payment['transaction_id'] ? htmlspecialchars($payment['transaction_id']) : '-'; ?></small></td>
                                        <td><small><?php echo $payment['notes'] ? htmlspecialchars(substr($payment['notes'], 0, 30)) . '...' : '-'; ?></small></td>
                                        <td style="white-space: nowrap;">
                                            <button class="btn btn-small" onclick="editPayment(<?php echo $payment['id']; ?>)" title="Edit">✏️</button>
                                            <button class="btn btn-small" onclick="viewReceipt(<?php echo $payment['id']; ?>, <?php echo $payment['student_id']; ?>)" title="View PDF">📄</button>
                                            <a href="?delete=<?php echo $payment['id']; ?>" class="btn btn-small" style="background: #e53e3e;" onclick="return confirm('Are you sure?');" title="Delete">🗑️</a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <h3>No payments found</h3>
                        <p>There are no payments matching your filters.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Edit Payment Modal -->
    <div id="editModal" class="modal" style="display: none;">
        <div class="modal-content" style="max-width: 500px;">
            <div class="modal-header">
                <h3>✏️ Edit Payment</h3>
                <span class="close" onclick="closeEditModal()">&times;</span>
            </div>
            <div class="modal-body">
                <form id="editPaymentForm" method="POST" action="">
                    <input type="hidden" name="action" value="update_status">
                    <input type="hidden" id="payment_id" name="payment_id">

                    <div class="form-group">
                        <label>Status</label>
                        <select id="payment_status" name="status" required>
                            <option value="pending">Pending</option>
                            <option value="completed">Completed</option>
                            <option value="failed">Failed</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Admin Notes</label>
                        <textarea id="admin_notes" name="notes" rows="3" placeholder="Add any notes..."></textarea>
                    </div>

                    <div style="display: flex; gap: 10px; margin-top: 20px;">
                        <button type="submit" class="btn" style="flex: 1;">Update Payment</button>
                        <button type="button" onclick="closeEditModal()" class="btn btn-secondary" style="flex: 1;">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="../assets/js/script.js"></script>
    <script>
        function editPayment(paymentId) {
            // Fetch payment data via AJAX
            const xhr = new XMLHttpRequest();
            xhr.open('GET', 'get_payment.php?id=' + paymentId, true);
            xhr.onload = function() {
                if (xhr.status === 200) {
                    const payment = JSON.parse(xhr.responseText);
                    document.getElementById('payment_id').value = paymentId;
                    document.getElementById('payment_status').value = payment.status;
                    document.getElementById('admin_notes').value = payment.notes || '';
                    document.getElementById('editModal').style.display = 'flex';
                }
            };
            xhr.send();
        }

        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
        }

        function viewReceipt(paymentId, studentId) {
            window.open('view_receipt_admin.php?payment_id=' + paymentId + '&student_id=' + studentId, '_blank', 'width=900,height=700');
        }

        window.onclick = function(event) {
            const modal = document.getElementById('editModal');
            if (event.target == modal) {
                closeEditModal();
            }
        }
    </script>
</body>

</html>
