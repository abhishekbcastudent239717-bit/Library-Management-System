<?php
session_start();
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

check_admin_login();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $name = sanitize_input($_POST['name']);
    $description = sanitize_input($_POST['description']);

    if (empty($name)) {
        $error = "Category name is required";
    } else {
        if ($_POST['action'] == 'add') {

            $check_query = "SELECT id FROM categories WHERE name = '$name'";
            $check_result = mysqli_query($conn, $check_query);

            if (mysqli_num_rows($check_result) > 0) {
                $error = "A category with this name already exists";
            } else {
                $query = "INSERT INTO categories (name, description) VALUES ('$name', '$description')";

                if (mysqli_query($conn, $query)) {
                    $success = "Category added successfully";
                } else {
                    $error = "Error adding category: " . mysqli_error($conn);
                }
            }
        } elseif ($_POST['action'] == 'edit') {
            $category_id = sanitize_input($_POST['category_id']);

            $check_query = "SELECT id FROM categories WHERE name = '$name' AND id != '$category_id'";
            $check_result = mysqli_query($conn, $check_query);

            if (mysqli_num_rows($check_result) > 0) {
                $error = "A category with this name already exists";
            } else {
                $query = "UPDATE categories SET name = '$name', description = '$description' WHERE id = '$category_id'";

                if (mysqli_query($conn, $query)) {
                    $success = "Category updated successfully";
                } else {
                    $error = "Error updating category: " . mysqli_error($conn);
                }
            }
        }
    }
}

if (isset($_GET['delete'])) {
    $category_id = sanitize_input($_GET['delete']);

    $check_query = "SELECT COUNT(*) as count FROM books WHERE category_id = '$category_id'";
    $check_result = mysqli_query($conn, $check_query);
    $check = mysqli_fetch_assoc($check_result);

    if ($check['count'] > 0) {
        $error = "Cannot delete category. It is being used by " . $check['count'] . " book(s).";
    } else {
        $query = "DELETE FROM categories WHERE id = '$category_id'";
        if (mysqli_query($conn, $query)) {
            $success = "Category deleted successfully";
        } else {
            $error = "Error deleting category: " . mysqli_error($conn);
        }
    }
}

$query = "SELECT c.*, COUNT(b.id) as book_count 
          FROM categories c 
          LEFT JOIN books b ON c.id = b.category_id 
          GROUP BY c.id 
          ORDER BY c.name";
$categories = mysqli_query($conn, $query);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Categories - Library Management System</title>
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
            <a href="categories.php" class="active">🗂️ Categories</a>
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
                <h2>Manage Categories</h2>
                <button onclick="openModal('addCategoryModal')" class="btn btn-small">+ Add New Category</button>
            </div>

            <!-- Categories Table -->
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th style="font-weight: 700;">ID</th>
                            <th style="font-weight: 700;">Name</th>
                            <th style="font-weight: 700;">Description</th>
                            <th style="font-weight: 700;">Books</th>
                            <th style="font-weight: 700;">Created</th>
                            <th style="font-weight: 700;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($category = mysqli_fetch_assoc($categories)): ?>
                            <tr>
                                <td><?php echo $category['id']; ?></td>
                                <td><?php echo $category['name']; ?></td>
                                <td><?php echo $category['description'] ?? 'N/A'; ?></td>
                                <td>
                                    <span class="badge badge-primary"><?php echo $category['book_count']; ?></span>
                                </td>
                                <td><?php echo format_date($category['created_at']); ?></td>
                                <td>
                                    <div class="actions">
                                        <button onclick="editCategory(<?php echo htmlspecialchars(json_encode($category)); ?>)" class="btn btn-small btn-secondary">Edit</button>
                                        <a href="?delete=<?php echo $category['id']; ?>" onclick="return confirmDelete('this category')" class="btn btn-small btn-danger">Delete</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Add Category Modal -->
    <div id="addCategoryModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Add New Category</h3>
                <span class="close-modal" onclick="closeModal('addCategoryModal')">&times;</span>
            </div>

            <form method="POST" action="" onsubmit="validateCategoryForm(event)">
                <input type="hidden" name="action" value="add">

                <div class="form-group">
                    <label for="name">Category Name *</label>
                    <input type="text" id="name" name="name">
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="3"></textarea>
                </div>

                <button type="submit" class="btn">Add Category</button>
            </form>
        </div>
    </div>

    <!-- Edit Category Modal -->
    <div id="editCategoryModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Edit Category</h3>
                <span class="close-modal" onclick="closeModal('editCategoryModal')">&times;</span>
            </div>

            <form method="POST" action="" onsubmit="validateCategoryForm(event)">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="category_id" id="edit_category_id">

                <div class="form-group">
                    <label for="edit_name">Category Name *</label>
                    <input type="text" id="edit_name" name="name">
                </div>

                <div class="form-group">
                    <label for="edit_description">Description</label>
                    <textarea id="edit_description" name="description" rows="3"></textarea>
                </div>

                <button type="submit" class="btn">Update Category</button>
            </form>
        </div>
    </div>

    <script src="../assets/js/script.js"></script>
    <script>
        function editCategory(category) {
            document.getElementById('edit_category_id').value = category.id;
            document.getElementById('edit_name').value = category.name;
            document.getElementById('edit_description').value = category.description || '';

            openModal('editCategoryModal');
        }
    </script>
</body>

</html>