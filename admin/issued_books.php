<?php
session_start();
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

check_admin_login();
update_overdue_books();

$error = '';
$success = '';

if (isset($_GET['return'])) {
    $issue_id = sanitize_input($_GET['return']);
    $today = date('Y-m-d');

    $query = "SELECT * FROM issued_books WHERE id = '$issue_id'";
    $result = mysqli_query($conn, $query);
    $issue = mysqli_fetch_assoc($result);

    if ($issue) {

        $fine = 0;
        if ($today > $issue['return_date']) {
            $overdue_days = calculate_overdue_days($issue['return_date']);
            $fine = calculate_fine($overdue_days);
        }

        $update_query = "UPDATE issued_books SET 
                        status = 'returned', 
                        actual_return_date = '$today',
                        fine_amount = '$fine'
                        WHERE id = '$issue_id'";

        if (mysqli_query($conn, $update_query)) {

            $book_query = "UPDATE books SET available_quantity = available_quantity + 1 WHERE id = '{$issue['book_id']}'";
            mysqli_query($conn, $book_query);

            if ($fine > 0) {
                $success = "Book returned successfully. Fine: ₹" . $fine;
            } else {
                $success = "Book returned successfully";
            }
        } else {
            $error = "Error returning book: " . mysqli_error($conn);
        }
    }
}

$filter = isset($_GET['filter']) ? $_GET['filter'] : 'active';

$query = "SELECT ib.*, b.title as book_title, b.author, b.isbn, 
          s.name as student_name, s.email as student_email
          FROM issued_books ib
          JOIN books b ON ib.book_id = b.id
          JOIN students s ON ib.student_id = s.id";

if ($filter == 'active') {
    $query .= " WHERE ib.status IN ('issued', 'overdue')";
} elseif ($filter == 'returned') {
    $query .= " WHERE ib.status = 'returned'";
} elseif ($filter == 'overdue') {
    $query .= " WHERE ib.status = 'overdue'";
}

$query .= " ORDER BY ib.issue_date DESC";
$issued_books = mysqli_query($conn, $query);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Issued Books - Library Management System</title>
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
            <span class="user-name">Welcome, <?php echo $_SESSION['admin_name']; ?></span>
            <button class="profile-btn" onclick="location.href='dashboard.php';" title="View your profile">
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
            <a href="issued_books.php" class="active">📋 Issued Books</a>
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

            <?php if ($error): ?>
                <div class="error-message"><?php echo $error; ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="success-message"><?php echo $success; ?></div>
            <?php endif; ?>

            <div class="page-title">
                <h2>Issued Books</h2>
                <div style="display: flex; gap: 10px;">
                    <a href="?filter=active" class="btn btn-small <?php echo $filter == 'active' ? '' : 'btn-secondary'; ?>">Active</a>
                    <a href="?filter=overdue" class="btn btn-small <?php echo $filter == 'overdue' ? 'btn-danger' : 'btn-secondary'; ?>">Overdue</a>
                    <a href="?filter=returned" class="btn btn-small <?php echo $filter == 'returned' ? 'btn-success' : 'btn-secondary'; ?>">Returned</a>
                </div>
            </div>

            <!-- Search Bar -->
            <div class="search-bar">
                <input type="text" id="searchInput" onkeyup="searchTable('searchInput', 'issuedTable')" placeholder="Search by book title, student name...">
            </div>

            <!-- Issued Books Table -->
            <div class="table-container">
                <table id="issuedTable">
                    <thead>
                        <tr>
                            <th style="font-weight: 700;">ID</th>
                            <th style="font-weight: 700;">Book Title</th>
                            <th style="font-weight: 700;">Student Name</th>
                            <th style="font-weight: 700;">Issue Date</th>
                            <th style="font-weight: 700;">Return Date</th>
                            <th style="font-weight: 700;">Actual Return</th>
                            <th style="font-weight: 700;">Status</th>
                            <th style="font-weight: 700;">Fine</th>
                            <th style="font-weight: 700;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($issued_books) > 0): ?>
                            <?php while ($issue = mysqli_fetch_assoc($issued_books)): ?>
                                <tr>
                                    <td><?php echo $issue['id']; ?></td>
                                    <td><?php echo $issue['book_title']; ?></td>
                                    <td><?php echo $issue['student_name']; ?></td>
                                    <td><?php echo format_date($issue['issue_date']); ?></td>
                                    <td><?php echo format_date($issue['return_date']); ?></td>
                                    <td>
                                        <?php echo $issue['actual_return_date'] ? format_date($issue['actual_return_date']) : '-'; ?>
                                    </td>
                                    <td>
                                        <?php if ($issue['status'] == 'issued'): ?>
                                            <span class="badge badge-success">Issued</span>
                                        <?php elseif ($issue['status'] == 'overdue'): ?>
                                            <span class="badge badge-danger">Overdue</span>
                                        <?php elseif ($issue['status'] == 'returned'): ?>
                                            <span class="badge badge-primary">Returned</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php
                                        if ($issue['fine_amount'] > 0) {
                                            echo '<span class="badge badge-warning">₹' . $issue['fine_amount'] . '</span>';
                                        } else {
                                            echo '-';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <?php if ($issue['status'] != 'returned'): ?>
                                            <a href="?return=<?php echo $issue['id']; ?>&filter=<?php echo $filter; ?>"
                                                onclick="return confirm('Mark this book as returned?')"
                                                class="btn btn-small btn-success">Return</a>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9">
                                    <div class="empty-state">
                                        <h3>No records found</h3>
                                        <p>There are no issued books matching your filter.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="../assets/js/script.js"></script>
</body>

</html>