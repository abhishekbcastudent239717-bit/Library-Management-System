<?php
session_start();
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

check_admin_login();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_announcement'])) {
        $title = sanitize_input($_POST['title']);
        $message = sanitize_input($_POST['message']);
        $priority = sanitize_input($_POST['priority']);

        if (empty($title) || empty($message)) {
            $error = "Title and message are required";
        } else {
            $query = "INSERT INTO announcements (title, message, priority, created_by, created_at) 
                      VALUES ('$title', '$message', '$priority', '{$_SESSION['admin_id']}', NOW())";

            if (mysqli_query($conn, $query)) {
                $success = "Announcement added successfully";
            } else {
                $error = "Error adding announcement";
            }
        }
    } elseif (isset($_POST['edit_announcement'])) {
        $id = sanitize_input($_POST['id']);
        $title = sanitize_input($_POST['title']);
        $message = sanitize_input($_POST['message']);
        $priority = sanitize_input($_POST['priority']);

        $query = "UPDATE announcements SET title='$title', message='$message', priority='$priority' WHERE id='$id'";

        if (mysqli_query($conn, $query)) {
            $success = "Announcement updated successfully";
        } else {
            $error = "Error updating announcement";
        }
    } elseif (isset($_POST['delete_announcement'])) {
        $id = sanitize_input($_POST['id']);
        $query = "DELETE FROM announcements WHERE id='$id'";

        if (mysqli_query($conn, $query)) {
            $success = "Announcement deleted successfully";
        } else {
            $error = "Error deleting announcement";
        }
    }
}

$query = "SELECT a.*, ad.name as admin_name FROM announcements a 
          LEFT JOIN admin ad ON a.created_by = ad.id 
          ORDER BY a.created_at DESC";
$announcements = mysqli_query($conn, $query);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Announcements - Library Management System</title>
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
            <a href="issued_books.php">📋 Issued Books</a>
            <a href="payments.php">💳 Payments</a>
            <a href="announcements.php" class="active">📢 Announcements</a>
            <a href="contact_messages.php">📞 Contact Messages</a>
        </div>
        <div class="sidebar-logout">
            <a href="logout.php">Logout</a>
        </div>
    </div>

    <div class="content-wrapper">
        <div class="dashboard">
            <div class="page-title">
                <h2>Manage Announcements</h2>
                <button onclick="openAddModal()" class="btn btn-small">+ Add Announcement</button>
            </div>

            <?php if ($error): ?>
                <div class="error-message"><?php echo $error; ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="success-message"><?php echo $success; ?></div>
            <?php endif; ?>

            <!-- Announcements Table -->
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th style="font-weight: 700;">Title</th>
                            <th style="font-weight: 700;">Message</th>
                            <th style="font-weight: 700;">Priority</th>
                            <th style="font-weight: 700;">Created By</th>
                            <th style="font-weight: 700;">Created At</th>
                            <th style="font-weight: 700;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($announcement = mysqli_fetch_assoc($announcements)): ?>
                            <tr>
                                <td><?php echo $announcement['title']; ?></td>
                                <td><?php echo substr($announcement['message'], 0, 50) . '...'; ?></td>
                                <td>
                                    <?php if ($announcement['priority'] == 'high'): ?>
                                        <span class="badge badge-danger">High</span>
                                    <?php elseif ($announcement['priority'] == 'medium'): ?>
                                        <span class="badge badge-warning">Medium</span>
                                    <?php else: ?>
                                        <span class="badge badge-primary">Low</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $announcement['admin_name']; ?></td>
                                <td><?php echo format_date($announcement['created_at']); ?></td>
                                <td class="actions">
                                    <button onclick='openEditModal(<?php echo json_encode($announcement); ?>)' class="btn btn-small">Edit</button>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Delete this announcement?')">
                                        <input type="hidden" name="id" value="<?php echo $announcement['id']; ?>">
                                        <button type="submit" name="delete_announcement" class="btn btn-small btn-danger">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Add Modal -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Add Announcement</h3>
                <span class="close-modal" onclick="closeAddModal()">&times;</span>
            </div>
            <form method="POST">
                <div class="form-group">
                    <label>Title</label>
                    <input type="text" name="title" required>
                </div>
                <div class="form-group">
                    <label>Message</label>
                    <textarea name="message" rows="5" required></textarea>
                </div>
                <div class="form-group">
                    <label>Priority</label>
                    <select name="priority" required>
                        <option value="low">Low</option>
                        <option value="medium">Medium</option>
                        <option value="high">High</option>
                    </select>
                </div>
                <button type="submit" name="add_announcement" class="btn">Add Announcement</button>
            </form>
        </div>
    </div>

    <!-- Edit Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Edit Announcement</h3>
                <span class="close-modal" onclick="closeEditModal()">&times;</span>
            </div>
            <form method="POST">
                <input type="hidden" name="id" id="edit_id">
                <div class="form-group">
                    <label>Title</label>
                    <input type="text" name="title" id="edit_title" required>
                </div>
                <div class="form-group">
                    <label>Message</label>
                    <textarea name="message" id="edit_message" rows="5" required></textarea>
                </div>
                <div class="form-group">
                    <label>Priority</label>
                    <select name="priority" id="edit_priority" required>
                        <option value="low">Low</option>
                        <option value="medium">Medium</option>
                        <option value="high">High</option>
                    </select>
                </div>
                <button type="submit" name="edit_announcement" class="btn">Update Announcement</button>
            </form>
        </div>
    </div>

    <script src="../assets/js/script.js"></script>
    <script>
        function openAddModal() {
            document.getElementById('addModal').classList.add('active');
        }

        function closeAddModal() {
            document.getElementById('addModal').classList.remove('active');
        }

        function openEditModal(announcement) {
            document.getElementById('edit_id').value = announcement.id;
            document.getElementById('edit_title').value = announcement.title;
            document.getElementById('edit_message').value = announcement.message;
            document.getElementById('edit_priority').value = announcement.priority;
            document.getElementById('editModal').classList.add('active');
        }

        function closeEditModal() {
            document.getElementById('editModal').classList.remove('active');
        }
    </script>
</body>

</html>