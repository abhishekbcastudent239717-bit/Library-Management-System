<?php

session_start();
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

check_admin_login();  // Ensure only admins can access

$error = '';    // Error messages
$success = '';  // Success messages

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {

    $title = sanitize_input($_POST['title']);              // Book title
    $author = sanitize_input($_POST['author']);            // Author name(s)
    $isbn = sanitize_input($_POST['isbn']);                // International Standard Book Number
    $category_id = sanitize_input($_POST['category_id']);  // Category (Fiction, Science, etc.)
    $quantity = sanitize_input($_POST['quantity']);        // Total copies available
    $published_year = sanitize_input($_POST['published_year']);  // Publication year
    $description = sanitize_input($_POST['description']);  // Book description/summary

    if (empty($title) || empty($author) || empty($isbn) || empty($quantity)) {
        $error = "All required fields must be filled";
    } else {

        if ($_POST['action'] == 'add') {

            $check_query = "SELECT id FROM books WHERE isbn = '$isbn'";
            $check_result = mysqli_query($conn, $check_query);

            if (mysqli_num_rows($check_result) > 0) {
                $error = "A book with this ISBN already exists";
            } else {

                $image_name = NULL;  // Default: no image

                if (isset($_FILES['book_image']) && $_FILES['book_image']['error'] == 0) {

                    $allowed = ['jpg', 'jpeg', 'png', 'gif'];
                    $filename = $_FILES['book_image']['name'];
                    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

                    if (in_array($ext, $allowed)) {

                        $image_name = uniqid('book_') . '.' . $ext;
                        $upload_path = '../uploads/books/' . $image_name;

                        if (!move_uploaded_file($_FILES['book_image']['tmp_name'], $upload_path)) {
                            $error = "Failed to upload image";
                            $image_name = NULL;
                        }
                    } else {
                        $error = "Invalid image format. Only JPG, JPEG, PNG, GIF allowed";
                    }
                }

                if (!$error) {

                    $query = "INSERT INTO books (title, author, isbn, category_id, quantity, available_quantity, published_year, description, image) 
                             VALUES ('$title', '$author', '$isbn', '$category_id', '$quantity', '$quantity', '$published_year', '$description', '$image_name')";

                    if (mysqli_query($conn, $query)) {
                        $success = "Book added successfully";
                    } else {
                        $error = "Error adding book: " . mysqli_error($conn);

                        if ($image_name && file_exists('../uploads/books/' . $image_name)) {
                            unlink('../uploads/books/' . $image_name);
                        }
                    }
                }
            }
        }

        elseif ($_POST['action'] == 'edit') {
            $book_id = sanitize_input($_POST['book_id']);

            $check_query = "SELECT id FROM books WHERE isbn = '$isbn' AND id != '$book_id'";
            $check_result = mysqli_query($conn, $check_query);

            if (mysqli_num_rows($check_result) > 0) {
                $error = "A book with this ISBN already exists";
            } else {

                $current_query = "SELECT quantity, available_quantity FROM books WHERE id = '$book_id'";
                $current_result = mysqli_query($conn, $current_query);
                $current = mysqli_fetch_assoc($current_result);

                $issued = $current['quantity'] - $current['available_quantity'];

                $new_available = $quantity - $issued;

                if ($new_available < 0) {
                    $error = "Quantity cannot be less than currently issued books (" . $issued . ")";
                } else {

                    $image_update = "";
                    if (isset($_FILES['book_image']) && $_FILES['book_image']['error'] == 0) {
                        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
                        $filename = $_FILES['book_image']['name'];
                        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

                        if (in_array($ext, $allowed)) {
                            $image_name = uniqid('book_') . '.' . $ext;
                            $upload_path = '../uploads/books/' . $image_name;

                            if (move_uploaded_file($_FILES['book_image']['tmp_name'], $upload_path)) {

                                $old_image_query = "SELECT image FROM books WHERE id = '$book_id'";
                                $old_image_result = mysqli_query($conn, $old_image_query);
                                $old_image_data = mysqli_fetch_assoc($old_image_result);

                                if ($old_image_data['image'] && file_exists('../uploads/books/' . $old_image_data['image'])) {
                                    unlink('../uploads/books/' . $old_image_data['image']);
                                }

                                $image_update = ", image = '$image_name'";
                            }
                        }
                    }

                    $query = "UPDATE books SET 
                             title = '$title',
                             author = '$author',
                             isbn = '$isbn',
                             category_id = '$category_id',
                             quantity = '$quantity',
                             available_quantity = '$new_available',
                             published_year = '$published_year',
                             description = '$description'
                             $image_update
                             WHERE id = '$book_id'";

                    if (mysqli_query($conn, $query)) {
                        $success = "Book updated successfully";
                    } else {
                        $error = "Error updating book: " . mysqli_error($conn);
                    }
                }
            }
        }
    }
}

if (isset($_GET['delete'])) {
    $book_id = sanitize_input($_GET['delete']);

    $check_query = "SELECT COUNT(*) as count FROM issued_books WHERE book_id = '$book_id' AND status IN ('issued', 'overdue')";
    $check_result = mysqli_query($conn, $check_query);
    $check = mysqli_fetch_assoc($check_result);

    if ($check['count'] > 0) {
        $error = "Cannot delete book. It is currently issued to students.";
    } else {

        $image_query = "SELECT image FROM books WHERE id = '$book_id'";
        $image_result = mysqli_query($conn, $image_query);
        $image_data = mysqli_fetch_assoc($image_result);

        $query = "DELETE FROM books WHERE id = '$book_id'";
        if (mysqli_query($conn, $query)) {

            if ($image_data['image'] && file_exists('../uploads/books/' . $image_data['image'])) {
                unlink('../uploads/books/' . $image_data['image']);
            }
            $success = "Book deleted successfully";
        } else {
            $error = "Error deleting book: " . mysqli_error($conn);
        }
    }
}

$query = "SELECT b.*, c.name as category_name 
          FROM books b 
          LEFT JOIN categories c ON b.category_id = c.id 
          ORDER BY b.created_at DESC";
$books = mysqli_query($conn, $query);

$categories_query = "SELECT * FROM categories ORDER BY name";
$categories = mysqli_query($conn, $categories_query);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Books - Library Management System</title>
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
            <a href="books.php" class="active">📚 Manage Books</a>
            <a href="students.php">👥 Manage Students</a>
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
                <h2>Manage Books</h2>
                <button onclick="openModal('addBookModal')" class="btn btn-small">+ Add New Book</button>
            </div>

            <!-- Search Bar -->
            <div class="search-bar">
                <input type="text" id="searchInput" onkeyup="searchTable('searchInput', 'booksTable')" placeholder="Search books by title, author, ISBN...">
            </div>

            <!-- Books Table -->
            <div class="table-container">
                <table id="booksTable">
                    <thead style="font-weight: 800;">
                        <tr>
                            <th style="font-weight: 700;">ID</th>
                            <th style="font-weight: 700;">Image</th>
                            <th style="font-weight: 700;">Title</th>
                            <th style="font-weight: 700;">Author</th>
                            <th style="font-weight: 700;">ISBN</th>
                            <th style="font-weight: 700;">Category</th>
                            <th style="font-weight: 700;">Total Qty</th>
                            <th style="font-weight: 700;">Available</th>
                            <th style="font-weight: 700;">Year</th>
                            <th style="font-weight: 700;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($book = mysqli_fetch_assoc($books)): ?>
                            <tr>
                                <td><?php echo $book['id']; ?></td>
                                <td>
                                    <?php
                                    $image_path = $book['image'] ? '../uploads/books/' . $book['image'] : '../assets/images/default-book.svg';
                                    ?>
                                    <img src="<?php echo $image_path; ?>" alt="Book" style="width: 50px; height: 65px; object-fit: cover; border-radius: 4px; border: 1px solid #e2e8f0;">
                                </td>
                                <td><?php echo $book['title']; ?></td>
                                <td><?php echo $book['author']; ?></td>
                                <td><?php echo $book['isbn']; ?></td>
                                <td><?php echo $book['category_name'] ?? 'N/A'; ?></td>
                                <td><?php echo $book['quantity']; ?></td>
                                <td>
                                    <?php if ($book['available_quantity'] > 0): ?>
                                        <span class="badge badge-success"><?php echo $book['available_quantity']; ?></span>
                                    <?php else: ?>
                                        <span class="badge badge-danger">0</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $book['published_year']; ?></td>
                                <td>
                                    <div class="actions">
                                        <button onclick="editBook(<?php echo htmlspecialchars(json_encode($book)); ?>)" class="btn btn-small btn-secondary">Edit</button>
                                        <a href="?delete=<?php echo $book['id']; ?>" onclick="return confirmDelete('this book')" class="btn btn-small btn-danger">Delete</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Add Book Modal -->
    <div id="addBookModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Add New Book</h3>
                <span class="close-modal" onclick="closeModal('addBookModal')">&times;</span>
            </div>

            <form method="POST" action="" enctype="multipart/form-data" onsubmit="validateBookForm(event)">
                <input type="hidden" name="action" value="add">

                <div class="form-group">
                    <label for="book_image">Book Image</label>
                    <input type="file" id="book_image" name="book_image" accept="image/*">
                    <small style="color: #718096; font-size: 12px;">Allowed: JPG, JPEG, PNG, GIF (Max 5MB)</small>
                </div>

                <div class="form-group">
                    <label for="title">Title *</label>
                    <input type="text" id="title" name="title">
                </div>

                <div class="form-group">
                    <label for="author">Author *</label>
                    <input type="text" id="author" name="author">
                </div>

                <div class="form-group">
                    <label for="isbn">ISBN *</label>
                    <input type="text" id="isbn" name="isbn">
                </div>

                <div class="form-group">
                    <label for="category_id">Category</label>
                    <select id="category_id" name="category_id">
                        <option value="">Select Category</option>
                        <?php
                        mysqli_data_seek($categories, 0);
                        while ($cat = mysqli_fetch_assoc($categories)):
                        ?>
                            <option value="<?php echo $cat['id']; ?>"><?php echo $cat['name']; ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="quantity">Quantity *</label>
                    <input type="number" id="quantity" name="quantity" min="1" value="1">
                </div>

                <div class="form-group">
                    <label for="published_year">Published Year</label>
                    <input type="number" id="published_year" name="published_year" min="1900" max="2099">
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="3"></textarea>
                </div>

                <button type="submit" class="btn">Add Book</button>
            </form>
        </div>
    </div>

    <!-- Edit Book Modal -->
    <div id="editBookModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Edit Book</h3>
                <span class="close-modal" onclick="closeModal('editBookModal')">&times;</span>
            </div>

            <form method="POST" action="" enctype="multipart/form-data" onsubmit="validateBookForm(event)">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="book_id" id="edit_book_id">

                <div class="form-group">
                    <label for="edit_book_image">Book Image</label>
                    <div id="current_image_preview" style="margin-bottom: 10px;"></div>
                    <input type="file" id="edit_book_image" name="book_image" accept="image/*">
                    <small style="color: #718096; font-size: 12px;">Upload new image to replace current one</small>
                </div>

                <div class="form-group">
                    <label for="edit_title">Title *</label>
                    <input type="text" id="edit_title" name="title">
                </div>

                <div class="form-group">
                    <label for="edit_author">Author *</label>
                    <input type="text" id="edit_author" name="author">
                </div>

                <div class="form-group">
                    <label for="edit_isbn">ISBN *</label>
                    <input type="text" id="edit_isbn" name="isbn">
                </div>

                <div class="form-group">
                    <label for="edit_category_id">Category</label>
                    <select id="edit_category_id" name="category_id">
                        <option value="">Select Category</option>
                        <?php
                        mysqli_data_seek($categories, 0);
                        while ($cat = mysqli_fetch_assoc($categories)):
                        ?>
                            <option value="<?php echo $cat['id']; ?>"><?php echo $cat['name']; ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="edit_quantity">Quantity *</label>
                    <input type="number" id="edit_quantity" name="quantity" min="1">
                </div>

                <div class="form-group">
                    <label for="edit_published_year">Published Year</label>
                    <input type="number" id="edit_published_year" name="published_year" min="1900" max="2099">
                </div>

                <div class="form-group">
                    <label for="edit_description">Description</label>
                    <textarea id="edit_description" name="description" rows="3"></textarea>
                </div>

                <button type="submit" class="btn">Update Book</button>
            </form>
        </div>
    </div>

    <script src="../assets/js/script.js"></script>
    <script>
        function editBook(book) {
            document.getElementById('edit_book_id').value = book.id;
            document.getElementById('edit_title').value = book.title;
            document.getElementById('edit_author').value = book.author;
            document.getElementById('edit_isbn').value = book.isbn;
            document.getElementById('edit_category_id').value = book.category_id || '';
            document.getElementById('edit_quantity').value = book.quantity;
            document.getElementById('edit_published_year').value = book.published_year || '';
            document.getElementById('edit_description').value = book.description || '';

            const imagePath = book.image ? '../uploads/books/' + book.image : '../assets/images/default-book.svg';
            document.getElementById('current_image_preview').innerHTML =
                '<img src="' + imagePath + '" alt="Current Book Image" style="width: 100px; height: 130px; object-fit: cover; border-radius: 4px; border: 1px solid #e2e8f0;">';

            openModal('editBookModal');
        }
    </script>
</body>

</html>