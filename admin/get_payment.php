<?php
session_start();
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

check_admin_login();

header('Content-Type: application/json');

$payment_id = isset($_GET['id']) ? sanitize_input($_GET['id']) : null;

if (!$payment_id) {
    echo json_encode(['error' => 'Invalid payment ID']);
    exit;
}

$query = "SELECT * FROM payments WHERE id = '$payment_id'";
$result = mysqli_query($conn, $query);

if (!$result || mysqli_num_rows($result) == 0) {
    echo json_encode(['error' => 'Payment not found']);
    exit;
}

$payment = mysqli_fetch_assoc($result);
echo json_encode($payment);
?>
