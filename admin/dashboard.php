<?php

session_start();  // Start session to access admin login state
require_once '../includes/db_connect.php';  // Database connection
require_once '../includes/functions.php';   // Utility functions

check_admin_login();

update_overdue_books();

$profile_error = '';    // Error messages for password change
$profile_success = '';  // Success messages for password change

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_password'])) {

    $admin_id = mysqli_real_escape_string($conn, $_SESSION['admin_id']);  // Get logged-in admin ID
    $current_password = $_POST['current_password'];  // User's current password (for verification)
    $new_password = $_POST['new_password'];          // New password user wants to set
    $confirm_password = $_POST['confirm_password'];  // Confirmation of new password

    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $profile_error = "All fields are required";
    }

    elseif ($new_password !== $confirm_password) {
        $profile_error = "New passwords do not match";
    }

    elseif (strlen($new_password) < 6) {
        $profile_error = "Password must be at least 6 characters";
    } else {

        $query = "SELECT password FROM admin WHERE id = '$admin_id'";
        $result = mysqli_query($conn, $query);

        if ($result && mysqli_num_rows($result) > 0) {
            $admin = mysqli_fetch_assoc($result);

            if (password_verify($current_password, $admin['password'])) {

                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

                $update_query = "UPDATE admin SET password = '$hashed_password' WHERE id = '$admin_id'";

                if (mysqli_query($conn, $update_query)) {

                    $profile_success = "Password changed successfully!";
                    $_POST = array();  // Clear form data to reset fields
                } else {

                    $profile_error = "Error updating password: " . mysqli_error($conn);
                }
            } else {

                $profile_error = "Current password is incorrect";
            }
        } else {

            $profile_error = "Admin account not found";
        }
    }
}

$stats = [];

$query = "SELECT COUNT(*) as total FROM books";
$result = mysqli_query($conn, $query);
$stats['total_books'] = mysqli_fetch_assoc($result)['total'];

$query = "SELECT COUNT(*) as total FROM students";
$result = mysqli_query($conn, $query);
$stats['total_students'] = mysqli_fetch_assoc($result)['total'];

$query = "SELECT COUNT(*) as total FROM issued_books WHERE status = 'issued' OR status = 'overdue'";
$result = mysqli_query($conn, $query);
$stats['issued_books'] = mysqli_fetch_assoc($result)['total'];

$query = "SELECT COUNT(*) as total FROM issued_books WHERE status = 'overdue'";
$result = mysqli_query($conn, $query);
$stats['overdue_books'] = mysqli_fetch_assoc($result)['total'];

$query = "SELECT COUNT(*) as total FROM categories";
$result = mysqli_query($conn, $query);
$stats['total_categories'] = mysqli_fetch_assoc($result)['total'];

$query = "SELECT SUM(available_quantity) as total FROM books";
$result = mysqli_query($conn, $query);
$stats['available_books'] = mysqli_fetch_assoc($result)['total'] ?? 0;

$query = "SELECT ib.*, b.title as book_title, s.name as student_name, s.email as student_email
          FROM issued_books ib
          JOIN books b ON ib.book_id = b.id
          JOIN students s ON ib.student_id = s.id
          WHERE ib.status IN ('issued', 'overdue')
          ORDER BY ib.issue_date DESC
          LIMIT 5";
$recent_issues = mysqli_query($conn, $query);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Library Management System</title>
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
            <span class="user-name">Welcome, <?php echo $_SESSION['admin_name']; ?> ⚙️</span>
            <button class="profile-btn" onclick="openProfileModal()" title="View your profile">
                <span class="profile-icon">👤</span>
                Profile
            </button>
        </div>
    </div>

    <!-- Sidebar Navigation -->
    <div class="sidebar">
        <div class="nav-menu">
            <a href="dashboard.php" class="active">📊 Dashboard</a>
            <a href="books.php">📚 Manage Books</a>
            <a href="students.php">👥 Manage Students</a>
            <a href="categories.php">🗂️ Categories</a>
            <a href="issue_book.php">📤 Issue Book</a>
            <a href="issued_books.php">📋 Issued Books</a>
            <a href="payments.php">💳 Payments</a>
            <a href="announcements.php">📢 Announcements</a>
            <a href="contact_messages.php">📞 Contact Messages</a>
        </div>
        <div class="sidebar-logout">
            <a href="logout.php">Logout</a>
        </div>
    </div>

    <div class="content-wrapper">
        <div class="dashboard">
            <h2 style="margin-bottom: 22px;">Admin Dashboard</h2>

            <!-- Statistics Cards -->
            <div class="stats-container">
                <div class="stat-card">
                    <h3>Total Books</h3>
                    <div class="stat-number"><?php echo $stats['total_books']; ?></div>
                </div>

                <div class="stat-card green">
                    <h3>Available Books</h3>
                    <div class="stat-number"><?php echo $stats['available_books']; ?></div>
                </div>

                <div class="stat-card blue">
                    <h3>Total Students</h3>
                    <div class="stat-number"><?php echo $stats['total_students']; ?></div>
                </div>

                <div class="stat-card orange">
                    <h3>Issued Books</h3>
                    <div class="stat-number"><?php echo $stats['issued_books']; ?></div>
                </div>

                <div class="stat-card red">
                    <h3>Overdue Books</h3>
                    <div class="stat-number"><?php echo $stats['overdue_books']; ?></div>
                </div>

                <div class="stat-card">
                    <h3>Categories</h3>
                    <div class="stat-number"><?php echo $stats['total_categories']; ?></div>
                </div>
            </div>

            <!-- Recent Issued Books -->
            <div class="card">
                <div class="card-header">
                    <h3>Recently Issued Books</h3>
                </div>

                <?php if (mysqli_num_rows($recent_issues) > 0): ?>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th style="font-weight: 700;">Book Title</th>
                                    <th style="font-weight: 700;">Student Name</th>
                                    <th style="font-weight: 700;">Issue Date</th>
                                    <th style="font-weight: 700;">Return Date</th>
                                    <th style="font-weight: 700;">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($issue = mysqli_fetch_assoc($recent_issues)): ?>
                                    <tr>
                                        <td><?php echo $issue['book_title']; ?></td>
                                        <td><?php echo $issue['student_name']; ?></td>
                                        <td><?php echo format_date($issue['issue_date']); ?></td>
                                        <td><?php echo format_date($issue['return_date']); ?></td>
                                        <td>
                                            <?php if ($issue['status'] == 'issued'): ?>
                                                <span class="badge badge-success">Issued</span>
                                            <?php elseif ($issue['status'] == 'overdue'): ?>
                                                <span class="badge badge-danger">Overdue</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <h3>No issued books</h3>
                        <p>There are no books currently issued to students.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Profile Modal -->
    <div id="profileModal" class="modal" style="display: none;">
        <div class="modal-content" style="max-width: 500px;">
            <div class="modal-header">
                <h3>⚙️ Admin Profile</h3>
                <span class="close" onclick="closeProfileModal()">&times;</span>
            </div>
            <div class="modal-body">
                <?php if ($profile_error): ?>
                    <div class="error-message"><?php echo $profile_error; ?></div>
                <?php endif; ?>

                <?php if ($profile_success): ?>
                    <div class="success-message"><?php echo $profile_success; ?></div>
                <?php endif; ?>

                <div style="padding: 20px; background: linear-gradient(135deg, #667eea15 0%, #764ba215 100%); border-radius: 8px; margin-bottom: 25px; border-left: 4px solid #667eea;">
                    <h4 style="margin: 0 0 15px 0; color: #2d3748; display: flex; align-items: center; gap: 8px;"><span>ℹ️</span> Profile Information</h4>
                    <p style="margin: 8px 0; color: #4a5568;"><strong>Name:</strong> <span style="color: #2d3748;"><?php echo $_SESSION['admin_name']; ?></span></p>
                    <p style="margin: 8px 0; color: #4a5568;"><strong>Email:</strong> <span style="color: #2d3748;"><?php echo $_SESSION['admin_email']; ?></span></p>
                </div>

                <h4 style="margin: 0 0 15px 0; color: #2d3748; display: flex; align-items: center; gap: 8px;">🔒 Change Password</h4>
                <form method="POST" action="" id="changePasswordForm">
                    <div class="form-group">
                        <label for="current_password">Current Password *</label>
                        <input type="password" id="current_password" name="current_password" required>
                    </div>

                    <div class="form-group">
                        <label for="new_password">New Password *</label>
                        <input type="password" id="new_password" name="new_password" required minlength="6">
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">Confirm New Password *</label>
                        <input type="password" id="confirm_password" name="confirm_password" required>
                    </div>

                    <div style="display: flex; gap: 10px; margin-top: 20px;">
                        <button type="submit" name="change_password" class="btn" style="flex: 1;">Change Password</button>
                        <button type="button" onclick="closeProfileModal()" class="btn btn-secondary" style="flex: 1;">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="../assets/js/script.js"></script>
    <script>
        function openProfileModal() {
            document.getElementById('profileModal').style.display = 'flex';
        }

        function closeProfileModal() {
            document.getElementById('profileModal').style.display = 'none';
        }

        window.onclick = function(event) {
            const modal = document.getElementById('profileModal');
            if (event.target == modal) {
                closeProfileModal();
            }
        }

        <?php if ($profile_error || $profile_success): ?>
            openProfileModal();
        <?php endif; ?>
    </script>
</body>

</html>