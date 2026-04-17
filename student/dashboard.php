<?php

session_start();  // Start session to access student login state
require_once '../includes/db_connect.php';  // Database connection
require_once '../includes/functions.php';   // Utility functions

check_student_login();

update_overdue_books();

$profile_error = '';    // Error messages for password change
$profile_success = '';  // Success messages for password change

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_password'])) {
    $student_id = mysqli_real_escape_string($conn, $_SESSION['student_id']);
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $profile_error = "All fields are required";
    } elseif ($new_password !== $confirm_password) {
        $profile_error = "New passwords do not match";
    } elseif (strlen($new_password) < 6) {
        $profile_error = "Password must be at least 6 characters";
    } else {

        $query = "SELECT password FROM students WHERE id = '$student_id'";
        $result = mysqli_query($conn, $query);

        if ($result && mysqli_num_rows($result) > 0) {
            $student_data = mysqli_fetch_assoc($result);

            if (password_verify($current_password, $student_data['password'])) {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $update_query = "UPDATE students SET password = '$hashed_password' WHERE id = '$student_id'";

                if (mysqli_query($conn, $update_query)) {
                    $profile_success = "Password changed successfully!";

                    $_POST = array();
                } else {
                    $profile_error = "Error updating password: " . mysqli_error($conn);
                }
            } else {
                $profile_error = "Current password is incorrect";
            }
        } else {
            $profile_error = "Student account not found";
        }
    }
}

$student_id = $_SESSION['student_id'];

$query = "SELECT * FROM students WHERE id = '$student_id'";
$result = mysqli_query($conn, $query);
$student = mysqli_fetch_assoc($result);

$stats = [];

$query = "SELECT COUNT(*) as total FROM issued_books WHERE student_id = '$student_id'";
$result = mysqli_query($conn, $query);
$stats['total_issued'] = mysqli_fetch_assoc($result)['total'];

$query = "SELECT COUNT(*) as total FROM issued_books WHERE student_id = '$student_id' AND status IN ('issued', 'overdue')";
$result = mysqli_query($conn, $query);
$stats['current_books'] = mysqli_fetch_assoc($result)['total'];

$query = "SELECT COUNT(*) as total FROM issued_books WHERE student_id = '$student_id' AND status = 'overdue'";
$result = mysqli_query($conn, $query);
$stats['overdue_books'] = mysqli_fetch_assoc($result)['total'];

$query = "SELECT SUM(fine_amount) as total FROM issued_books WHERE student_id = '$student_id' AND fine_amount > 0";
$result = mysqli_query($conn, $query);
$stats['total_fine'] = mysqli_fetch_assoc($result)['total'] ?? 0;

$query = "SELECT ib.*, b.title, b.author, b.isbn, c.name as category_name
          FROM issued_books ib
          JOIN books b ON ib.book_id = b.id
          LEFT JOIN categories c ON b.category_id = c.id
          WHERE ib.student_id = '$student_id' AND ib.status IN ('issued', 'overdue')
          ORDER BY ib.issue_date DESC";
$current_books = mysqli_query($conn, $query);

$query = "SELECT b.*, c.name as category_name 
          FROM books b 
          LEFT JOIN categories c ON b.category_id = c.id 
          WHERE b.available_quantity > 0 
          ORDER BY b.title 
          LIMIT 5";
$available_books = mysqli_query($conn, $query);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - Library Management System</title>
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
            <a href="my_books.php">📚 My Books</a>
            <a href="available_books.php">📖 Available Books</a>
            <a href="announcements.php">📢 Announcements</a>
            <a href="pay_dues.php">💳 Pay Dues</a>
            <a href="contact.php">📞 Contact Library</a>
        </div>
        <div class="sidebar-logout">
            <a href="logout.php">Logout</a>
        </div>
    </div>

    <div class="content-wrapper">
        <div class="dashboard">
            <h2 style="margin-bottom: 20px;">Student Dashboard</h2>

            <!-- Statistics Cards -->
            <div class="stats-container" style="grid-template-columns: repeat(2, 1fr);">
                <div class="stat-card">
                    <h3>Total Books Issued</h3>
                    <div class="stat-number"><?php echo $stats['total_issued']; ?></div>
                </div>

                <div class="stat-card blue">
                    <h3>Currently Issued</h3>
                    <div class="stat-number"><?php echo $stats['current_books']; ?></div>
                </div>

                <div class="stat-card red">
                    <h3>Overdue Books</h3>
                    <div class="stat-number"><?php echo $stats['overdue_books']; ?></div>
                </div>

                <div class="stat-card orange">
                    <h3>Total Fine</h3>
                    <div class="stat-number">₹<?php echo $stats['total_fine']; ?></div>
                </div>
            </div>

            <!-- Currently Issued Books -->
            <div class="card">
                <div class="card-header">
                    <h3>Currently Issued Books</h3>
                </div>

                <?php if (mysqli_num_rows($current_books) > 0): ?>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Book Title</th>
                                    <th>Author</th>
                                    <th>Issue Date</th>
                                    <th>Return Date</th>
                                    <th>Status</th>
                                    <th>Fine</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($book = mysqli_fetch_assoc($current_books)): ?>
                                    <tr>
                                        <td><?php echo $book['title']; ?></td>
                                        <td><?php echo $book['author']; ?></td>
                                        <td><?php echo format_date($book['issue_date']); ?></td>
                                        <td><?php echo format_date($book['return_date']); ?></td>
                                        <td>
                                            <?php if ($book['status'] == 'issued'): ?>
                                                <span class="badge badge-success">Issued</span>
                                            <?php elseif ($book['status'] == 'overdue'): ?>
                                                <span class="badge badge-danger">Overdue</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php
                                            if ($book['fine_amount'] > 0) {
                                                echo '<span class="badge badge-warning">₹' . $book['fine_amount'] . '</span>';
                                            } else {
                                                echo '-';
                                            }
                                            ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <h3>No issued books</h3>
                        <p>You don't have any books currently issued.</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Available Books -->
            <div class="card">
                <div class="card-header">
                    <h3>Recently Available Books</h3>
                </div>

                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Author</th>
                                <th>Category</th>
                                <th>Available</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($book = mysqli_fetch_assoc($available_books)): ?>
                                <tr>
                                    <td><?php echo $book['title']; ?></td>
                                    <td><?php echo $book['author']; ?></td>
                                    <td><?php echo $book['category_name'] ?? 'N/A'; ?></td>
                                    <td><span class="badge badge-success"><?php echo $book['available_quantity']; ?></span></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>

                <div style="text-align: center; margin-top: 20px;">
                    <a href="available_books.php" class="btn btn-small">View All Books</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Profile Modal -->
    <div id="profileModal" class="modal" style="display: none;">
        <div class="modal-content" style="max-width: 500px;">
            <div class="modal-header">
                <h3>👤 Student Profile</h3>
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
                    <p style="margin: 8px 0; color: #4a5568;"><strong>Name:</strong> <span style="color: #2d3748;"><?php echo $_SESSION['student_name']; ?></span></p>
                    <p style="margin: 8px 0; color: #4a5568;"><strong>Email:</strong> <span style="color: #2d3748;"><?php echo $_SESSION['student_email']; ?></span></p>
                    <p style="margin: 8px 0; color: #4a5568;"><strong>Mobile No:</strong> <span style="color: #2d3748;"><?php echo $student['phone'] ?? 'N/A'; ?></span></p>
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