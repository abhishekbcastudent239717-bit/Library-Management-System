<?php
session_start();
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

check_student_login();

$query = "SELECT a.*, ad.name as admin_name FROM announcements a 
          LEFT JOIN admin ad ON a.created_by = ad.id 
          ORDER BY 
          CASE a.priority 
              WHEN 'high' THEN 1 
              WHEN 'medium' THEN 2 
              WHEN 'low' THEN 3 
          END,
          a.created_at DESC";
$announcements = mysqli_query($conn, $query);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Announcements - Library Management System</title>
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
            <a href="available_books.php">📖 Available Books</a>
            <a href="announcements.php" class="active">📢 Announcements</a>
            <a href="pay_dues.php">💳 Pay Dues</a>
            <a href="contact.php">📞 Contact Library</a>
        </div>
        <div class="sidebar-logout">
            <a href="logout.php">Logout</a>
        </div>
    </div>

    <div class="content-wrapper">
        <div class="dashboard">
            <h2 style="margin-bottom: 20px;">📢 Announcements</h2>

            <?php if (mysqli_num_rows($announcements) > 0): ?>
                <?php while ($announcement = mysqli_fetch_assoc($announcements)): ?>
                    <div class="card announcement-card <?php echo $announcement['priority']; ?>">
                        <div class="announcement-header">
                            <div>
                                <h3><?php echo $announcement['title']; ?></h3>
                                <small>Posted by <?php echo $announcement['admin_name']; ?> on <?php echo format_date($announcement['created_at']); ?></small>
                            </div>
                            <?php if ($announcement['priority'] == 'high'): ?>
                                <span class="badge badge-danger">High Priority</span>
                            <?php elseif ($announcement['priority'] == 'medium'): ?>
                                <span class="badge badge-warning">Medium Priority</span>
                            <?php else: ?>
                                <span class="badge badge-primary">Low Priority</span>
                            <?php endif; ?>
                        </div>
                        <div class="announcement-body">
                            <p><?php echo nl2br($announcement['message']); ?></p>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="empty-state">
                    <h3>No Announcements</h3>
                    <p>There are no announcements at the moment.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="../assets/js/script.js"></script>
</body>

</html>