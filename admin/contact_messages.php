<?php
session_start();
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

check_admin_login();

$error = '';
$success = '';

if (isset($_GET['mark_read']) && !empty($_GET['mark_read'])) {
    $msg_id = sanitize_input($_GET['mark_read']);
    $query = "UPDATE contact_messages SET status = 'read' WHERE id = '$msg_id'";
    if (mysqli_query($conn, $query)) {
        $success = "Message marked as read";
    }
}

if (isset($_GET['delete']) && !empty($_GET['delete'])) {
    $msg_id = sanitize_input($_GET['delete']);
    $query = "DELETE FROM contact_messages WHERE id = '$msg_id'";
    if (mysqli_query($conn, $query)) {
        $success = "Message deleted successfully";
    } else {
        $error = "Error deleting message";
    }
}

$status_filter = isset($_GET['status']) ? sanitize_input($_GET['status']) : '';
$type_filter = isset($_GET['type']) ? sanitize_input($_GET['type']) : '';

$query = "SELECT cm.*, s.name as student_name_ref 
          FROM contact_messages cm 
          LEFT JOIN students s ON cm.student_id = s.id 
          WHERE 1=1";

if (!empty($status_filter)) {
    $query .= " AND cm.status = '$status_filter'";
}

if (!empty($type_filter)) {
    $query .= " AND cm.user_type = '$type_filter'";
}

$query .= " ORDER BY cm.created_at DESC";
$messages = mysqli_query($conn, $query);

$new_count_query = "SELECT COUNT(*) as count FROM contact_messages WHERE status = 'new'";
$new_count = mysqli_fetch_assoc(mysqli_query($conn, $new_count_query))['count'];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Messages - Library Management System</title>
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
            <a href="announcements.php">📢 Announcements</a>
            <a href="contact_messages.php" class="active">📞 Contact Messages <?php if ($new_count > 0): ?><span class="badge badge-danger" style="margin-left: 5px; font-size: 11px;"><?php echo $new_count; ?></span><?php endif; ?></a>
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
                <h2>Contact Messages</h2>
            </div>

            <!-- Filters -->
            <div class="card" style="margin-bottom: 20px;">
                <form method="GET" action="">
                    <div style="display: grid; grid-template-columns: 1fr 1fr auto; gap: 15px; align-items: end;">
                        <div class="form-group" style="margin-bottom: 0;">
                            <label for="status" style="color: #000000 !important;">Filter by Status</label>
                            <select id="status" name="status">
                                <option value="">All Status</option>
                                <option value="new" <?php echo $status_filter == 'new' ? 'selected' : ''; ?>>New</option>
                                <option value="read" <?php echo $status_filter == 'read' ? 'selected' : ''; ?>>Read</option>
                                <option value="replied" <?php echo $status_filter == 'replied' ? 'selected' : ''; ?>>Replied</option>
                            </select>
                        </div>

                        <div class="form-group" style="margin-bottom: 0;">
                            <label for="type" style="color: #000000 !important;">Filter by Type</label>
                            <select id="type" name="type">
                                <option value="">All Types</option>
                                <option value="student" <?php echo $type_filter == 'student' ? 'selected' : ''; ?>>Student</option>
                                <option value="public" <?php echo $type_filter == 'public' ? 'selected' : ''; ?>>Public</option>
                            </select>
                        </div>

                        <button type="submit" class="btn" style="width: auto; padding: 12px 30px;">Filter</button>
                    </div>
                </form>
            </div>

            <!-- Messages Table -->
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th style="font-weight: 700;">ID</th>
                            <th style="font-weight: 700;">Name</th>
                            <th style="font-weight: 700;">Email</th>
                            <th style="font-weight: 700;">Phone</th>
                            <th style="font-weight: 700;">Subject</th>
                            <th style="font-weight: 700;">Type</th>
                            <th style="font-weight: 700;">Status</th>
                            <th style="font-weight: 700;">Date</th>
                            <th style="font-weight: 700;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($messages) > 0): ?>
                            <?php while ($msg = mysqli_fetch_assoc($messages)): ?>
                                <tr style="<?php echo $msg['status'] == 'new' ? 'background: #fffaeb;' : ''; ?>">
                                    <td><?php echo $msg['id']; ?></td>
                                    <td><?php echo $msg['name']; ?></td>
                                    <td><?php echo $msg['email']; ?></td>
                                    <td><?php echo $msg['phone'] ?? 'N/A'; ?></td>
                                    <td><?php echo substr($msg['subject'], 0, 40) . (strlen($msg['subject']) > 40 ? '...' : ''); ?></td>
                                    <td>
                                        <?php if ($msg['user_type'] == 'student'): ?>
                                            <span class="badge badge-primary">Student</span>
                                        <?php else: ?>
                                            <span class="badge" style="background: #718096;">Public</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($msg['status'] == 'new'): ?>
                                            <span class="badge badge-warning">New</span>
                                        <?php elseif ($msg['status'] == 'read'): ?>
                                            <span class="badge badge-primary">Read</span>
                                        <?php else: ?>
                                            <span class="badge badge-success">Replied</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo format_date($msg['created_at']); ?></td>
                                    <td>
                                        <div class="actions">
                                            <button onclick="viewMessage(<?php echo htmlspecialchars(json_encode($msg)); ?>)" class="btn btn-small btn-secondary">View</button>
                                            <?php if ($msg['status'] == 'new'): ?>
                                                <a href="?mark_read=<?php echo $msg['id']; ?>" class="btn btn-small" style="background: #38a169;">Mark Read</a>
                                            <?php endif; ?>
                                            <a href="?delete=<?php echo $msg['id']; ?>" onclick="return confirmDelete('this message')" class="btn btn-small btn-danger">Delete</a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" style="text-align: center; padding: 40px; color: #999;">
                                    No messages found
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- View Message Modal -->
    <div id="viewMessageModal" class="modal">
        <div class="modal-content" style="max-width: 600px;">
            <div class="modal-header">
                <h3>Message Details</h3>
                <span class="close-modal" onclick="closeModal('viewMessageModal')">&times;</span>
            </div>

            <div style="padding: 20px;">
                <div style="margin-bottom: 15px;">
                    <strong style="color: #4a5568;">From:</strong>
                    <p id="msg_name" style="margin: 5px 0; color: #2d3748;"></p>
                </div>

                <div style="margin-bottom: 15px;">
                    <strong style="color: #4a5568;">Email:</strong>
                    <p id="msg_email" style="margin: 5px 0; color: #2d3748;"></p>
                </div>

                <div style="margin-bottom: 15px;">
                    <strong style="color: #4a5568;">Phone:</strong>
                    <p id="msg_phone" style="margin: 5px 0; color: #2d3748;"></p>
                </div>

                <div style="margin-bottom: 15px;">
                    <strong style="color: #4a5568;">Subject:</strong>
                    <p id="msg_subject" style="margin: 5px 0; color: #2d3748; font-weight: 600;"></p>
                </div>

                <div style="margin-bottom: 15px;">
                    <strong style="color: #4a5568;">Message:</strong>
                    <div id="msg_message" style="margin: 10px 0; padding: 15px; background: #f7fafc; border-radius: 6px; color: #2d3748; line-height: 1.6;"></div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div>
                        <strong style="color: #4a5568;">Type:</strong>
                        <p id="msg_type" style="margin: 5px 0;"></p>
                    </div>
                    <div>
                        <strong style="color: #4a5568;">Date:</strong>
                        <p id="msg_date" style="margin: 5px 0; color: #2d3748;"></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="../assets/js/script.js"></script>
    <script>
        function viewMessage(msg) {
            document.getElementById('msg_name').textContent = msg.name;
            document.getElementById('msg_email').textContent = msg.email;
            document.getElementById('msg_phone').textContent = msg.phone || 'N/A';
            document.getElementById('msg_subject').textContent = msg.subject;
            document.getElementById('msg_message').innerHTML = msg.message.replace(/\n/g, '<br>');

            const typeHtml = msg.user_type === 'student' ?
                '<span class="badge badge-primary">Student</span>' :
                '<span class="badge" style="background: #718096;">Public</span>';
            document.getElementById('msg_type').innerHTML = typeHtml;

            document.getElementById('msg_date').textContent = new Date(msg.created_at).toLocaleString();

            openModal('viewMessageModal');
        }
    </script>
</body>

</html>