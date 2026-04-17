<?php
session_start();
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

check_admin_login();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $name = sanitize_input($_POST['name']);
    $email = sanitize_input($_POST['email']);
    $phone = sanitize_input($_POST['phone']);
    $address = sanitize_input($_POST['address']);

    if (empty($name) || empty($email)) {
        $error = "Name and email are required";
    } else {
        if ($_POST['action'] == 'add') {
            $password = $_POST['password'];

            if (empty($password)) {
                $error = "Password is required for new students";
            } else {

                $check_query = "SELECT id FROM students WHERE email = '$email'";
                $check_result = mysqli_query($conn, $check_query);

                if (mysqli_num_rows($check_result) > 0) {
                    $error = "A student with this email already exists";
                } else {
                    $password_hash = password_hash($password, PASSWORD_DEFAULT);

                    $query = "INSERT INTO students (name, email, password, phone, address) 
                             VALUES ('$name', '$email', '$password_hash', '$phone', '$address')";

                    if (mysqli_query($conn, $query)) {
                        $success = "Student added successfully";
                    } else {
                        $error = "Error adding student: " . mysqli_error($conn);
                    }
                }
            }
        } elseif ($_POST['action'] == 'edit') {
            $student_id = sanitize_input($_POST['student_id']);

            $check_query = "SELECT id FROM students WHERE email = '$email' AND id != '$student_id'";
            $check_result = mysqli_query($conn, $check_query);

            if (mysqli_num_rows($check_result) > 0) {
                $error = "A student with this email already exists";
            } else {
                $query = "UPDATE students SET 
                         name = '$name',
                         email = '$email',
                         phone = '$phone',
                         address = '$address'";

                if (!empty($_POST['password'])) {
                    $password_hash = password_hash($_POST['password'], PASSWORD_DEFAULT);
                    $query .= ", password = '$password_hash'";
                }

                $query .= " WHERE id = '$student_id'";

                if (mysqli_query($conn, $query)) {
                    $success = "Student updated successfully";
                } else {
                    $error = "Error updating student: " . mysqli_error($conn);
                }
            }
        }
    }
}

if (isset($_GET['delete'])) {
    $student_id = sanitize_input($_GET['delete']);

    $check_query = "SELECT COUNT(*) as count FROM issued_books WHERE student_id = '$student_id' AND status IN ('issued', 'overdue')";
    $check_result = mysqli_query($conn, $check_query);
    $check = mysqli_fetch_assoc($check_result);

    if ($check['count'] > 0) {
        $error = "Cannot delete student. They have books currently issued.";
    } else {
        $query = "DELETE FROM students WHERE id = '$student_id'";
        if (mysqli_query($conn, $query)) {
            $success = "Student deleted successfully";
        } else {
            $error = "Error deleting student: " . mysqli_error($conn);
        }
    }
}

$query = "SELECT s.*, 
          (SELECT COUNT(*) FROM issued_books WHERE student_id = s.id AND status IN ('issued', 'overdue')) as active_books
          FROM students s 
          ORDER BY s.created_at DESC";
$students = mysqli_query($conn, $query);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Students - Library Management System</title>
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
            <a href="students.php" class="active">👥 Manage Students</a>
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

            <?php if ($error): ?>
                <div class="error-message"><?php echo $error; ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="success-message"><?php echo $success; ?></div>
            <?php endif; ?>

            <div class="page-title">
                <h2>Manage Students</h2>
                <button onclick="openModal('addStudentModal')" class="btn btn-small">+ Add New Student</button>
            </div>

            <!-- Search Bar -->
            <div class="search-bar">
                <input type="text" id="searchInput" onkeyup="searchTable('searchInput', 'studentsTable')" placeholder="Search students by name, email...">
            </div>

            <!-- Students Table -->
            <div class="table-container">
                <table id="studentsTable">
                    <thead>
                        <tr>
                            <th style="font-weight: 700;">ID</th>
                            <th style="font-weight: 700;">Name</th>
                            <th style="font-weight: 700;">Email</th>
                            <th style="font-weight: 700;">Phone</th>
                            <th style="font-weight: 700;">Active Books</th>
                            <th style="font-weight: 700;">Joined Date</th>
                            <th style="font-weight: 700;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($student = mysqli_fetch_assoc($students)): ?>
                            <tr>
                                <td><?php echo $student['id']; ?></td>
                                <td><?php echo $student['name']; ?></td>
                                <td><?php echo $student['email']; ?></td>
                                <td><?php echo $student['phone'] ?? 'N/A'; ?></td>
                                <td>
                                    <?php if ($student['active_books'] > 0): ?>
                                        <span class="badge badge-warning"><?php echo $student['active_books']; ?></span>
                                    <?php else: ?>
                                        <span class="badge badge-success">0</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo format_date($student['created_at']); ?></td>
                                <td>
                                    <div class="actions">
                                        <button onclick="editStudent(<?php echo htmlspecialchars(json_encode($student)); ?>)" class="btn btn-small btn-secondary">Edit</button>
                                        <a href="?delete=<?php echo $student['id']; ?>" onclick="return confirmDelete('this student')" class="btn btn-small btn-danger">Delete</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Add Student Modal -->
    <div id="addStudentModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Add New Student</h3>
                <span class="close-modal" onclick="closeModal('addStudentModal')">&times;</span>
            </div>

            <form method="POST" action="" onsubmit="validateStudentForm(event)">
                <input type="hidden" name="action" value="add">

                <div class="form-group">
                    <label for="name">Name *</label>
                    <input type="text" id="name" name="name">
                </div>

                <div class="form-group">
                    <label for="email">Email *</label>
                    <input type="email" id="email" name="email">
                </div>

                <div class="form-group">
                    <label for="password">Password *</label>
                    <input type="password" id="password" name="password">
                </div>

                <div class="form-group">
                    <label for="phone">Phone</label>
                    <input type="text" id="phone" name="phone" maxlength="10">
                </div>

                <div class="form-group">
                    <label for="address">Address</label>
                    <textarea id="address" name="address" rows="3"></textarea>
                </div>

                <button type="submit" class="btn">Add Student</button>
            </form>
        </div>
    </div>

    <!-- Edit Student Modal -->
    <div id="editStudentModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Edit Student</h3>
                <span class="close-modal" onclick="closeModal('editStudentModal')">&times;</span>
            </div>

            <form method="POST" action="" onsubmit="validateStudentForm(event)">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="student_id" id="edit_student_id">

                <div class="form-group">
                    <label for="edit_name">Name *</label>
                    <input type="text" id="edit_name" name="name">
                </div>

                <div class="form-group">
                    <label for="edit_email">Email *</label>
                    <input type="email" id="edit_email" name="email">
                </div>

                <div class="form-group">
                    <label for="edit_password">Password (leave empty to keep current)</label>
                    <input type="password" id="edit_password" name="password">
                </div>

                <div class="form-group">
                    <label for="edit_phone">Phone</label>
                    <input type="text" id="edit_phone" name="phone" maxlength="10">
                </div>

                <div class="form-group">
                    <label for="edit_address">Address</label>
                    <textarea id="edit_address" name="address" rows="3"></textarea>
                </div>

                <button type="submit" class="btn">Update Student</button>
            </form>
        </div>
    </div>

    <script src="../assets/js/script.js"></script>
    <script>
        function editStudent(student) {
            document.getElementById('edit_student_id').value = student.id;
            document.getElementById('edit_name').value = student.name;
            document.getElementById('edit_email').value = student.email;
            document.getElementById('edit_phone').value = student.phone || '';
            document.getElementById('edit_address').value = student.address || '';
            document.getElementById('edit_password').value = '';

            openModal('editStudentModal');
        }
    </script>
</body>

</html>