<?php

session_start();
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

check_admin_login();  // Ensure only admins can issue books

$error = '';    // Error messages
$success = '';  // Success messages

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $student_id = sanitize_input($_POST['student_id']);    // ID of student receiving book
    $book_id = sanitize_input($_POST['book_id']);          // ID of book being issued
    $return_date = sanitize_input($_POST['return_date']);  // Expected return date
    $issue_date = date('Y-m-d');                           // Today's date (auto-set)

    if (empty($student_id) || empty($book_id) || empty($return_date)) {
        $error = "All fields are required";
    }

    elseif ($return_date <= $issue_date) {
        $error = "Return date must be in the future";
    } else {

        $check_query = "SELECT available_quantity FROM books WHERE id = '$book_id'";
        $check_result = mysqli_query($conn, $check_query);
        $book = mysqli_fetch_assoc($check_result);

        if ($book['available_quantity'] < 1) {

            $error = "This book is not currently available";
        } else {

            $query = "INSERT INTO issued_books (book_id, student_id, issue_date, return_date, status) 
                     VALUES ('$book_id', '$student_id', '$issue_date', '$return_date', 'issued')";

            if (mysqli_query($conn, $query)) {

                $update_query = "UPDATE books SET available_quantity = available_quantity - 1 WHERE id = '$book_id'";
                mysqli_query($conn, $update_query);

                $success = "Book issued successfully";
            } else {
                $error = "Error issuing book: " . mysqli_error($conn);
            }
        }
    }
}

$students_query = "SELECT id, name, email FROM students ORDER BY name";
$students = mysqli_query($conn, $students_query);

$books_query = "SELECT b.id, b.title, b.author, b.isbn, b.available_quantity, c.name as category_name 
                FROM books b 
                LEFT JOIN categories c ON b.category_id = c.id 
                WHERE b.available_quantity > 0 
                ORDER BY b.title";
$books = mysqli_query($conn, $books_query);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Issue Book - Library Management System</title>
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
            <a href="issue_book.php" class="active">📤 Issue Book</a>
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

            <?php if ($error): ?>
                <div class="error-message"><?php echo $error; ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="success-message"><?php echo $success; ?></div>
            <?php endif; ?>

            <div class="card" style="max-width: 600px; margin: 0 auto;">
                <div class="card-header">
                    <h3>Issue Book to Student</h3>
                </div>

                <form method="POST" action="" onsubmit="validateIssueForm(event)">
                    <div class="form-group">
                        <label for="student_id" style="color: #000000 !important;">Select Student *</label>
                        <select id="student_id" name="student_id">
                            <option value="" style="color: #000000 !important; ">-- Select Student --</option>
                            <?php while ($student = mysqli_fetch_assoc($students)): ?>
                                <option value="<?php echo $student['id']; ?>" style="color: #000000 !important; ">
                                    <?php echo $student['name']; ?> (<?php echo $student['email']; ?>)
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="book_id" style="color: #000000 !important; ">Select Book *</label>
                        <select id="book_id" name="book_id">
                            <option value="" style="color: #000000 !important; ">-- Select Book --</option>
                            <?php while ($book = mysqli_fetch_assoc($books)): ?>
                                <option value="<?php echo $book['id']; ?>">
                                    <?php echo $book['title']; ?> by <?php echo $book['author']; ?>
                                    (Available: <?php echo $book['available_quantity']; ?>)
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="issue_date" style="color: #000000 !important; ">Issue Date</label>
                        <input type="date" id="issue_date" value="<?php echo date('Y-m-d'); ?>" disabled>
                    </div>

                    <div class="form-group">
                        <label style="color: #000000 !important;" for="return_date">Return Date *</label>
                        <input type="date" id="return_date" name="return_date" min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>">
                    </div>

                    <button type="submit" class="btn">Issue Book</button>
                </form>
            </div>
        </div>
    </div>

    <script src="../assets/js/script.js"></script>
</body>

</html>