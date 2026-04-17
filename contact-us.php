<?php
session_start();
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = sanitize_input($_POST['name']);
    $email = sanitize_input($_POST['email']);
    $phone = sanitize_input($_POST['phone']);
    $subject = sanitize_input($_POST['subject']);
    $message = sanitize_input($_POST['message']);

    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        $error = "All required fields must be filled";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email address";
    } else {
        $query = "INSERT INTO contact_messages (name, email, phone, subject, message, user_type) 
                 VALUES ('$name', '$email', '$phone', '$subject', '$message', 'public')";

        if (mysqli_query($conn, $query)) {
            $success = "Thank you for contacting us! We will get back to you soon.";
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
    <title>Contact Us - Library Management System</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/auth.css">
</head>

<body>
    <div class="contact-container">
        <div class="contact-wrapper">
            <div class="contact-header">
                <h1>📚 Contact Library</h1>
                <p>Have questions? We'd love to hear from you.</p>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px;">
                <!-- Contact Form -->
                <div class="login-box" style="max-width: 100%; padding: 40px;">
                    <h2 style="margin-bottom: 25px; font-size: 24px;">Send us a Message</h2>

                    <?php if ($error): ?>
                        <div class="error-message"><?php echo $error; ?></div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                        <div class="success-message"><?php echo $success; ?></div>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <div class="form-group">
                            <label for="name" style="color: #f4e4c1 !important;">Your Name *</label>
                            <input type="text" id="name" name="name" placeholder="Enter your name" required value="<?php echo isset($_POST['name']) ? $_POST['name'] : ''; ?>">
                        </div>

                        <div class="form-group">
                            <label for="email" style="color: #f4e4c1 !important;">Email Address *</label>
                            <input type="email" id="email" name="email" placeholder="Enter your email" required value="<?php echo isset($_POST['email']) ? $_POST['email'] : ''; ?>">
                        </div>

                        <div class="form-group">
                            <label for="phone" style="color: #f4e4c1 !important;">Phone Number</label>
                            <input type="tel" id="phone" name="phone" placeholder="Enter your phone number" value="<?php echo isset($_POST['phone']) ? $_POST['phone'] : ''; ?>">
                        </div>

                        <div class="form-group">
                            <label for="subject" style="color: #f4e4c1 !important;">Subject *</label>
                            <input type="text" id="subject" name="subject" placeholder="What is this regarding?" required value="<?php echo isset($_POST['subject']) ? $_POST['subject'] : ''; ?>">
                        </div>

                        <div class="form-group">
                            <label for="message" style="color: #f4e4c1 !important;">Message *</label>
                            <textarea id="message" name="message" rows="5" placeholder="Type your message here..." required><?php echo isset($_POST['message']) ? $_POST['message'] : ''; ?></textarea>
                        </div>

                        <button type="submit" class="btn">Send Message</button>
                    </form>
                </div>

                <!-- Contact Information -->
                <div class="login-box" style="max-width: 100%; padding: 40px;">
                    <h2 style="margin-bottom: 25px; font-size: 24px;">Contact Information</h2>

                    <div style="margin-bottom: 25px;">
                        <h3 style="color: #f4e4c1; font-size: 16px; margin-bottom: 10px;">📧 Email</h3>
                        <p style="margin: 0; color: #d4a574; font-size: 15px;">library@college.com</p>
                    </div>

                    <div style="margin-bottom: 25px;">
                        <h3 style="color: #f4e4c1; font-size: 16px; margin-bottom: 10px;">📞 Phone</h3>
                        <p style="margin: 0; color: #d4a574; font-size: 15px;">+91 9508703778</p>
                    </div>

                    <div style="margin-bottom: 25px;">
                        <h3 style="color: #f4e4c1; font-size: 16px; margin-bottom: 10px;">🕒 Working Hours</h3>
                        <p style="margin: 0 0 5px 0; color: #d4a574; font-size: 14px;">Monday - Friday: 9:00 AM - 6:00 PM</p>
                        <p style="margin: 0 0 5px 0; color: #d4a574; font-size: 14px;">Saturday: 10:00 AM - 4:00 PM</p>
                        <p style="margin: 0; color: #d4a574; font-size: 14px;">Sunday: Closed</p>
                    </div>

                    <div style="margin-bottom: 25px;">
                        <h3 style="color: #f4e4c1; font-size: 16px; margin-bottom: 10px;">📍 Address</h3>
                        <p style="margin: 0; color: #d4a574; font-size: 14px; line-height: 1.6;">
                            College Library<br>
                            Main Campus Building<br>
                            City, State - 123456
                        </p>
                    </div>

                    <div style="padding: 20px; background: rgba(0, 0, 0, 0.2); border-radius: 6px; border: 1px solid rgba(212, 165, 116, 0.3);">
                        <h3 style="color: #f4e4c1; font-size: 16px; margin-bottom: 10px;">💡 Note</h3>
                        <p style="margin: 0; color: #d4a574; font-size: 13px; line-height: 1.6;">
                            If you're interested in library membership or have questions about registration,
                            feel free to reach out. We typically respond within 24-48 hours.
                        </p>
                    </div>
                </div>
            </div>

            <div class="back-to-login">
                <a href="login.php">← Back to Login</a>
            </div>

            <!-- Google Map Section -->
            <div class="map-section">
                <h2 style="text-align: center; color: #f4e4c1; font-size: 28px; margin-bottom: 20px; text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);">📍 Find Us on Map</h2>
                <div class="map-container">

                    <iframe
                        src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d1798.5616927171527!2d85.10544554850618!3d25.634030143256116!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x39ed577e021a41ff%3A0x619f6dd3ed125a3d!2sP%26M%20Mall!5e0!3m2!1sen!2sin!4v1765906408385!5m2!1sen!2sin"
                        width="100%"
                        height="450"
                        style="border:0; border-radius: 8px; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);"
                        allowfullscreen=""
                        loading="lazy"
                        referrerpolicy="no-referrer-when-downgrade">
                    </iframe>
                </div>
            </div>
        </div>
    </div>

    <script src="assets/js/script.js"></script>
</body>

</html>