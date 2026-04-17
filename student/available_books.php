<?php

session_start();
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

check_student_login();  // Ensure only logged-in students can view

$search = isset($_GET['search']) ? sanitize_input($_GET['search']) : '';

$category = isset($_GET['category']) ? sanitize_input($_GET['category']) : '';

$query = "SELECT b.*, c.name as category_name 
          FROM books b 
          LEFT JOIN categories c ON b.category_id = c.id 
          WHERE b.available_quantity > 0";  // Only show available books

if (!empty($search)) {

    $query .= " AND (b.title LIKE '%$search%' OR b.author LIKE '%$search%' OR b.isbn LIKE '%$search%')";
}

if (!empty($category)) {
    $query .= " AND b.category_id = '$category'";
}

$query .= " ORDER BY b.title";
$books = mysqli_query($conn, $query);

$categories_query = "SELECT * FROM categories ORDER BY name";
$categories = mysqli_query($conn, $categories_query);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Available Books - Library Management System</title>
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
            <a href="my_books.php">📚 My Books</a>
            <a href="available_books.php" class="active">📖 Available Books</a>
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

            <h2 style="margin-bottom: 20px;">Available Books</h2>

            <!-- Search and Filter -->
            <div class="card" style="margin-bottom: 20px;">
                <form method="GET" action="">
                    <div style="display: grid; grid-template-columns: 2fr 1fr auto; gap: 10px;">
                        <div class="form-group" style="margin-bottom: 0;">
                            <input type="text" name="search" placeholder="Search by title, author, or ISBN..." value="<?php echo htmlspecialchars($search); ?>">
                        </div>

                        <div class="form-group" style="margin-bottom: 0;">
                            <select name="category">
                                <option value="">All Categories</option>
                                <?php while ($cat = mysqli_fetch_assoc($categories)): ?>
                                    <option value="<?php echo $cat['id']; ?>" <?php echo $category == $cat['id'] ? 'selected' : ''; ?>>
                                        <?php echo $cat['name']; ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <button type="submit" class="btn" style="width: auto; padding: 12px 30px;">Search</button>
                    </div>
                </form>
            </div>

            <!-- Books Grid -->
            <?php if (mysqli_num_rows($books) > 0): ?>
                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px;">
                    <?php while ($book = mysqli_fetch_assoc($books)): ?>
                        <div class="card">
                            <?php
                            $image_path = $book['image'] ? '../uploads/books/' . $book['image'] : '../assets/images/default-book.svg';
                            ?>
                            <div style="text-align: center; margin-bottom: 15px;">
                                <img src="<?php echo $image_path; ?>" alt="<?php echo $book['title']; ?>" style="width: 100%; max-width: 200px; height: 250px; object-fit: cover; border-radius: 6px; border: 2px solid #e2e8f0; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                            </div>
                            <h3 style="color: #667eea; margin-bottom: 10px;"><?php echo $book['title']; ?></h3>
                            <p style="margin-bottom: 5px;"><strong>Author:</strong> <?php echo $book['author']; ?></p>
                            <p style="margin-bottom: 5px;"><strong>ISBN:</strong> <?php echo $book['isbn']; ?></p>
                            <p style="margin-bottom: 5px;"><strong>Category:</strong> <?php echo $book['category_name'] ?? 'N/A'; ?></p>
                            <?php if ($book['published_year']): ?>
                                <p style="margin-bottom: 5px;"><strong>Year:</strong> <?php echo $book['published_year']; ?></p>
                            <?php endif; ?>
                            <?php if ($book['description']): ?>
                                <p style="margin-bottom: 10px; color: #666; font-size: 14px;"><?php echo substr($book['description'], 0, 100); ?><?php echo strlen($book['description']) > 100 ? '...' : ''; ?></p>
                            <?php endif; ?>
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 15px; padding-top: 15px; border-top: 1px solid #eee;">
                                <span class="badge badge-success">Available: <?php echo $book['available_quantity']; ?></span>
                                <span style="color: #999; font-size: 12px;">Total: <?php echo $book['quantity']; ?></span>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <h3>No books found</h3>
                    <p>No books are currently available matching your search criteria.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="../assets/js/script.js"></script>
</body>

</html>