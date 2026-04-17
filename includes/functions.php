<?php

function sanitize_input($data)
{
    global $conn;
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return mysqli_real_escape_string($conn, $data);
}

function check_admin_login()
{
    if (!isset($_SESSION['admin_id'])) {
        header("Location: ../login.php");
        exit();
    }
}

function check_student_login()
{
    if (!isset($_SESSION['student_id'])) {
        header("Location: ../login.php");
        exit();
    }
}

function calculate_overdue_days($return_date)
{
    $today = new DateTime();
    $return = new DateTime($return_date);
    if ($today > $return) {
        $diff = $today->diff($return);
        return $diff->days;
    }
    return 0;
}

function calculate_fine($overdue_days)
{
    return $overdue_days * 5;
}

function format_date($date)
{
    return date('d M, Y', strtotime($date));
}

function update_overdue_books()
{
    global $conn;
    $today = date('Y-m-d');
    $query = "UPDATE issued_books 
              SET status = 'overdue', 
                  fine_amount = DATEDIFF('$today', return_date) * 5
              WHERE return_date < '$today' 
              AND status = 'issued'";
    mysqli_query($conn, $query);
}
function ensure_payments_table_exists()
{
    global $conn;
    
    // Check if payments table exists
    $result = mysqli_query($conn, "SHOW TABLES LIKE 'payments'");
    
    if (mysqli_num_rows($result) == 0) {
        // Create the table
        $create_table_sql = "CREATE TABLE IF NOT EXISTS `payments` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `student_id` int(11) NOT NULL,
          `amount` decimal(10,2) NOT NULL,
          `payment_method` enum('card','upi','bank transfer','demo') DEFAULT 'demo',
          `transaction_id` varchar(100) DEFAULT NULL,
          `status` enum('pending','completed','failed','cancelled') DEFAULT 'pending',
          `payment_date` datetime DEFAULT CURRENT_TIMESTAMP,
          `notes` text DEFAULT NULL,
          `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`),
          KEY `student_id` (`student_id`),
          KEY `status` (`status`),
          KEY `payment_date` (`payment_date`),
          CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci AUTO_INCREMENT=1;";
        
        mysqli_query($conn, $create_table_sql);
    }
}

?>