<?php
session_start();
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

check_student_login();
update_overdue_books();

$student_id = $_SESSION['student_id'];

$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';

$query = "SELECT ib.*, b.title, b.author, b.isbn, c.name as category_name
          FROM issued_books ib
          JOIN books b ON ib.book_id = b.id
          LEFT JOIN categories c ON b.category_id = c.id
          WHERE ib.student_id = '$student_id'";

if ($filter == 'active') {
    $query .= " AND ib.status IN ('issued', 'overdue')";
} elseif ($filter == 'returned') {
    $query .= " AND ib.status = 'returned'";
} elseif ($filter == 'overdue') {
    $query .= " AND ib.status = 'overdue'";
}

$query .= " ORDER BY ib.issue_date DESC";
$books = mysqli_query($conn, $query);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Books - Library Management System</title>
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
            <button class="profile-btn" onclick="alert('Profile feature available on dashboard'); location.href='dashboard.php';" title="View your profile">
                <span class="profile-icon">👤</span>
                Profile
            </button>
        </div>
    </div>

    <!-- Sidebar Navigation -->
    <div class="sidebar">
        <div class="nav-menu">
            <a href="dashboard.php">📊 Dashboard</a>
            <a href="my_books.php" class="active">📚 My Books</a>
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

            <div class="page-title">
                <h2>My Books</h2>
                <div style="display: flex; gap: 10px;">
                    <a href="?filter=all" class="btn btn-small <?php echo $filter == 'all' ? '' : 'btn-secondary'; ?>">All</a>
                    <a href="?filter=active" class="btn btn-small <?php echo $filter == 'active' ? '' : 'btn-secondary'; ?>">Active</a>
                    <a href="?filter=overdue" class="btn btn-small <?php echo $filter == 'overdue' ? 'btn-danger' : 'btn-secondary'; ?>">Overdue</a>
                    <a href="?filter=returned" class="btn btn-small <?php echo $filter == 'returned' ? 'btn-success' : 'btn-secondary'; ?>">Returned</a>
                </div>
            </div>

            <!-- Books Table -->
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Book Title</th>
                            <th>Author</th>
                            <th>Category</th>
                            <th>Issue Date</th>
                            <th>Return Date</th>
                            <th>Actual Return</th>
                            <th>Status</th>
                            <th>Fine</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($books) > 0): ?>
                            <?php while ($book = mysqli_fetch_assoc($books)): ?>
                                <tr>
                                    <td><?php echo $book['title']; ?></td>
                                    <td><?php echo $book['author']; ?></td>
                                    <td><?php echo $book['category_name'] ?? 'N/A'; ?></td>
                                    <td><?php echo format_date($book['issue_date']); ?></td>
                                    <td><?php echo format_date($book['return_date']); ?></td>
                                    <td>
                                        <?php echo $book['actual_return_date'] ? format_date($book['actual_return_date']) : '-'; ?>
                                    </td>
                                    <td>
                                        <?php if ($book['status'] == 'issued'): ?>
                                            <span class="badge badge-success">Issued</span>
                                        <?php elseif ($book['status'] == 'overdue'): ?>
                                            <span class="badge badge-danger">Overdue</span>
                                        <?php elseif ($book['status'] == 'returned'): ?>
                                            <span class="badge badge-primary">Returned</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php
                                        if ($book['status'] == 'overdue' && !$book['actual_return_date']) {
                                            $overdue_days = calculate_overdue_days($book['return_date']);
                                            $fine = calculate_fine($overdue_days);
                                            echo '<span class="badge badge-warning">₹' . $fine . '</span>';
                                        } elseif ($book['fine_amount'] > 0) {
                                            echo '<span class="badge badge-warning">₹' . $book['fine_amount'] . '</span>';
                                        } else {
                                            echo '-';
                                        }
                                        ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8">
                                    <div class="empty-state">
                                        <h3>No books found</h3>
                                        <p>You haven't issued any books yet.</p>
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