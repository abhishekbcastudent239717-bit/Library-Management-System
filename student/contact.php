<?php
session_start();
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

check_student_login();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $subject = sanitize_input($_POST['subject']);
    $message = sanitize_input($_POST['message']);
    $phone = sanitize_input($_POST['phone']);

    $student_id = $_SESSION['student_id'];
    $name = $_SESSION['student_name'];
    $email = $_SESSION['student_email'];

    if (empty($subject) || empty($message)) {
        $error = "Subject and message are required";
    } else {
        $query = "INSERT INTO contact_messages (name, email, phone, subject, message, user_type, student_id) 
                 VALUES ('$name', '$email', '$phone', '$subject', '$message', 'student', '$student_id')";

        if (mysqli_query($conn, $query)) {
            $success = "Your message has been sent successfully! Library staff will contact you soon.";
            $_POST = array(); // Clear form
        } else {
            $error = "Error sending message. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Library - Library Management System</title>
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
            <a href="announcements.php">📢 Announcements</a>
            <a href="pay_dues.php">💳 Pay Dues</a>
            <a href="contact.php" class="active">📞 Contact Library</a>
        </div>
        <div class="sidebar-logout">
            <a href="logout.php">Logout</a>
        </div>
    </div>

    <div class="content-wrapper">
        <div class="dashboard">
            <h2 style="margin-bottom: 20px;">📞 Contact Library</h2>

            <?php if ($error): ?>
                <div class="error-message"><?php echo $error; ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="success-message"><?php echo $success; ?></div>
            <?php endif; ?>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px;">
                <!-- Contact Form -->
                <div class="card">
                    <h3 style="margin-bottom: 20px; color: #2d3748;">Send us a Message</h3>

                    <form method="POST" action="">
                        <div class="form-group">
                            <label for="name">Your Name</label>
                            <input type="text" id="name" value="<?php echo $_SESSION['student_name']; ?>" disabled style="background: #f7fafc;">
                        </div>

                        <div class="form-group">
                            <label for="email">Your Email</label>
                            <input type="email" id="email" value="<?php echo $_SESSION['student_email']; ?>" disabled style="background: #f7fafc;">
                        </div>

                        <div class="form-group">
                            <label for="phone">Phone Number (Optional)</label>
                            <input type="tel" id="phone" name="phone" placeholder="Enter your phone number" value="<?php echo isset($_POST['phone']) ? $_POST['phone'] : ''; ?>">
                        </div>

                        <div class="form-group">
                            <label for="subject">Subject *</label>
                            <input type="text" id="subject" name="subject" placeholder="What is this regarding?" required>
                        </div>

                        <div class="form-group">
                            <label for="message">Message *</label>
                            <textarea id="message" name="message" rows="6" placeholder="Type your message here..." required></textarea>
                        </div>

                        <button type="submit" class="btn">Send Message</button>
                    </form>
                </div>

                <!-- Contact Information -->
                <div>
                    <div class="card" style="margin-bottom: 20px;">
                        <h3 style="margin-bottom: 15px; color: #2d3748;">📍 Library Information</h3>

                        <div style="margin-bottom: 15px;">
                            <strong style="color: #4a5568; display: block; margin-bottom: 5px;">📧 Email:</strong>
                            <p style="margin: 0; color: #718096;">library@college.com</p>
                        </div>

                        <div style="margin-bottom: 15px;">
                            <strong style="color: #4a5568; display: block; margin-bottom: 5px;">📞 Phone:</strong>
                            <p style="margin: 0; color: #718096;">+91 1234567890</p>
                        </div>

                        <div style="margin-bottom: 15px;">
                            <strong style="color: #4a5568; display: block; margin-bottom: 5px;">🕒 Working Hours:</strong>
                            <p style="margin: 0; color: #718096;">Monday - Friday: 9:00 AM - 6:00 PM</p>
                            <p style="margin: 0; color: #718096;">Saturday: 10:00 AM - 4:00 PM</p>
                            <p style="margin: 0; color: #718096;">Sunday: Closed</p>
                        </div>

                        <div>
                            <strong style="color: #4a5568; display: block; margin-bottom: 5px;">📍 Address:</strong>
                            <p style="margin: 0; color: #718096;">College Library<br>Main Campus Building<br>City, State - 123456</p>
                        </div>
                    </div>

                    <div class="card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                        <h3 style="margin-bottom: 15px; color: white;">💡 Quick Tips</h3>
                        <ul style="margin: 0; padding-left: 20px; line-height: 1.8;">
                            <li>Book-related queries usually get response within 24 hours</li>
                            <li>For urgent issues, call us directly</li>
                            <li>Include your student ID for faster assistance</li>
                            <li>Check announcements for library updates</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="../assets/js/script.js"></script>
</body>

</html>