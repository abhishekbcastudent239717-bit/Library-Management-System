<?php

session_start();  // Start PHP session for maintaining user login state
require_once 'includes/db_connect.php';  // Database connection
require_once 'includes/functions.php';   // Common utility functions

$error = '';    // Error messages for invalid login
$success = '';  // Success messages (currently unused)

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $email = sanitize_input($_POST['email']);  // Clean email input (prevent SQL injection & XSS)
    $password = $_POST['password'];             // Password (not sanitized - will use password_verify)

    if (empty($email) || empty($password)) {
        $error = "All fields are required";  // Both fields must be filled
    } else {

        $query = "SELECT * FROM admin WHERE email = '$email' LIMIT 1";
        $result = mysqli_query($conn, $query);

        if (mysqli_num_rows($result) == 1) {

            $admin = mysqli_fetch_assoc($result);

            if (password_verify($password, $admin['password'])) {

                $_SESSION['admin_id'] = $admin['id'];        // Store admin ID
                $_SESSION['admin_name'] = $admin['name'];    // Store admin name
                $_SESSION['admin_email'] = $admin['email'];  // Store admin email

                header("Location: admin/dashboard.php");
                exit();  // Stop script execution after redirect
            } else {

                $error = "Invalid email or password";
            }
        } else {

            $query = "SELECT * FROM students WHERE email = '$email' LIMIT 1";
            $result = mysqli_query($conn, $query);

            if (mysqli_num_rows($result) == 1) {

                $student = mysqli_fetch_assoc($result);

                if (password_verify($password, $student['password'])) {

                    $_SESSION['student_id'] = $student['id'];        // Store student ID
                    $_SESSION['student_name'] = $student['name'];    // Store student name
                    $_SESSION['student_email'] = $student['email'];  // Store student email

                    header("Location: student/dashboard.php");
                    exit();  // Stop script execution after redirect
                } else {

                    $error = "Invalid email or password";
                }
            } else {

                $error = "Invalid email or password";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Library Management System</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/auth.css">
</head>

<body>
    <div class="login-container">
        <div class="login-box">
            <h2>Library<br>Management System</h2>

            <?php if ($error): ?>
                <div class="error-message"><?php echo $error; ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="success-message"><?php echo $success; ?></div>
            <?php endif; ?>

            <form method="POST" action="" onsubmit="validateLoginForm(event)">

                <div class="form-group">
                    <label for="email" style="color: #f4e4c1 !important;">Email</label>
                    <input type="email" id="email" name="email" placeholder="Enter your email">
                </div>

                <div class="form-group">
                    <label for="password" style="color: #f4e4c1 !important;">Password</label>
                    <div style="position: relative;">
                        <input type="password" id="password" name="password" placeholder="Enter your password">
                        <span onclick="togglePassword()" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); cursor: pointer; color: #5d3a1a; font-size: 18px; user-select: none;">👁️</span>
                    </div>
                </div>
                <br><br>
                <button type="submit" class="btn">Login</button>
            </form>

            <!-- <div style="text-align: center; margin-top: 25px; color: #d4a574; font-size: 13px; padding: 15px; background: rgba(0, 0, 0, 0.2); border-radius: 4px; border: 1px solid rgba(212, 165, 116, 0.3);">
                <p style="margin: 0 0 10px 0;"><strong style="color: #f4e4c1;">Demo Credentials:</strong></p>
                <p style="margin: 5px 0;">Admin: admin@library.com / password</p>
                <p style="margin: 5px 0;">Student: vivek@student.com / password</p>
            </div> -->
            <br>
            <div style="text-align: center; margin-top: 15px;">
                <a href="contact-us.php" style="color: #d4a574; text-decoration: none; font-size: 14px; transition: color 0.3s ease;">📞 Contact Us</a>
            </div>
        </div>
    </div>

    <script src="assets/js/script.js"></script>
    <script>
        function togglePassword() {
            const passwordField = document.getElementById('password');
            const toggleIcon = event.target;

            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                toggleIcon.textContent = '🙈';
            } else {
                passwordField.type = 'password';
                toggleIcon.textContent = '👁️';
            }
        }
    </script>
</body>

</html>