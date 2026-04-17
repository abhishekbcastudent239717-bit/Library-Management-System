<?php
session_start();
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';
require_once '../includes/pdf_generator.php';

check_admin_login();

$payment_id = isset($_GET['payment_id']) ? sanitize_input($_GET['payment_id']) : null;

if (!$payment_id) {
    die("Invalid payment ID");
}

// Fetch payment details
$query = "SELECT p.*, s.name as student_name, s.email, s.phone, s.id as student_id
          FROM payments p
          JOIN students s ON p.student_id = s.id
          WHERE p.id = '$payment_id'";

$result = mysqli_query($conn, $query);

if (!$result || mysqli_num_rows($result) == 0) {
    die("Payment not found.");
}

$payment = mysqli_fetch_assoc($result);
$student = [
    'id' => $payment['student_id'],
    'name' => $payment['student_name'],
    'email' => $payment['email'],
    'phone' => $payment['phone']
];

$html = generate_payment_receipt_html($payment, $student);

echo $html;
?>
