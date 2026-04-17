<?php
/**
 * Simple PDF Generator for Payment Receipts
 * Generates HTML-based PDF content for payment receipts
 */

function generate_payment_receipt_html($payment, $student, $admin_details = []) {
    $html = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="utf-8">
        <title>Payment Receipt</title>
        <style>
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }
            
            body {
                font-family: Arial, sans-serif;
                color: #333;
                line-height: 1.6;
            }
            
            .container {
                max-width: 800px;
                margin: 0 auto;
                padding: 20px;
            }
            
            .header {
                text-align: center;
                margin-bottom: 30px;
                border-bottom: 3px solid #667eea;
                padding-bottom: 20px;
            }
            
            .header h1 {
                font-size: 28px;
                color: #667eea;
                margin-bottom: 5px;
            }
            
            .header p {
                color: #666;
                font-size: 14px;
            }
            
            .receipt-title {
                text-align: center;
                font-size: 20px;
                font-weight: bold;
                margin: 20px 0;
                color: #2d3748;
            }
            
            .receipt-number {
                text-align: center;
                color: #666;
                font-size: 12px;
                margin-bottom: 20px;
            }
            
            .content {
                margin: 30px 0;
            }
            
            .section {
                margin-bottom: 25px;
            }
            
            .section-title {
                font-weight: bold;
                color: #667eea;
                border-bottom: 2px solid #e2e8f0;
                padding-bottom: 5px;
                margin-bottom: 10px;
                font-size: 14px;
                text-transform: uppercase;
            }
            
            .row {
                display: flex;
                justify-content: space-between;
                padding: 8px 0;
                border-bottom: 1px solid #f0f4f8;
            }
            
            .row.highlight {
                background-color: #f7fafc;
                padding: 10px;
            }
            
            .label {
                font-weight: 600;
                color: #4a5568;
                width: 40%;
            }
            
            .value {
                text-align: right;
                color: #2d3748;
            }
            
            .amount-box {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                padding: 20px;
                border-radius: 8px;
                margin: 20px 0;
            }
            
            .amount-box .label {
                color: rgba(255, 255, 255, 0.9);
            }
            
            .amount-box .value {
                color: white;
                font-size: 24px;
                font-weight: bold;
            }
            
            .status-badge {
                display: inline-block;
                padding: 5px 15px;
                border-radius: 20px;
                font-size: 12px;
                font-weight: bold;
                text-transform: uppercase;
            }
            
            .status-completed {
                background-color: #c6f6d5;
                color: #22543d;
            }
            
            .status-pending {
                background-color: #fed7d7;
                color: #742a2a;
            }
            
            .footer {
                margin-top: 40px;
                text-align: center;
                font-size: 12px;
                color: #666;
                border-top: 2px solid #e2e8f0;
                padding-top: 20px;
            }
            
            .footer-note {
                margin-top: 10px;
                font-style: italic;
                color: #999;
            }
            
            @media print {
                body {
                    margin: 0;
                    padding: 0;
                }
                .container {
                    max-width: 100%;
                }
                .no-print {
                    display: none;
                }
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>📚 Library Management System</h1>
                <p>Cimage College Library | Payment Receipt</p>
            </div>
            
            <div class="receipt-title">Payment Receipt</div>
            <div class="receipt-number">Receipt #' . str_pad($payment['id'], 6, '0', STR_PAD_LEFT) . ' | ' . date('d M Y, H:i A', strtotime($payment['payment_date'])) . '</div>
            
            <div class="content">
                <!-- Student Information -->
                <div class="section">
                    <div class="section-title">Student Information</div>
                    <div class="row">
                        <span class="label">Name:</span>
                        <span class="value">' . htmlspecialchars($student['name']) . '</span>
                    </div>
                    <div class="row">
                        <span class="label">Email:</span>
                        <span class="value">' . htmlspecialchars($student['email']) . '</span>
                    </div>
                    <div class="row">
                        <span class="label">Student ID:</span>
                        <span class="value">' . str_pad($student['id'], 4, '0', STR_PAD_LEFT) . '</span>
                    </div>
                    <div class="row">
                        <span class="label">Phone:</span>
                        <span class="value">' . htmlspecialchars($student['phone'] ?? 'N/A') . '</span>
                    </div>
                </div>
                
                <!-- Payment Details -->
                <div class="section">
                    <div class="section-title">Payment Details</div>
                    <div class="amount-box">
                        <div class="row">
                            <span class="label">Amount Paid:</span>
                            <span class="value">₹' . number_format($payment['amount'], 2) . '</span>
                        </div>
                    </div>
                    <div class="row">
                        <span class="label">Payment Method:</span>
                        <span class="value">' . ucfirst($payment['payment_method']) . '</span>
                    </div>
                    <div class="row">
                        <span class="label">Transaction ID:</span>
                        <span class="value">' . ($payment['transaction_id'] ? htmlspecialchars($payment['transaction_id']) : 'N/A') . '</span>
                    </div>
                    <div class="row">
                        <span class="label">Payment Status:</span>
                        <span class="value"><span class="status-badge status-' . $payment['status'] . '">' . ucfirst($payment['status']) . '</span></span>
                    </div>
                    <div class="row">
                        <span class="label">Payment Date:</span>
                        <span class="value">' . date('d M Y, H:i A', strtotime($payment['payment_date'])) . '</span>
                    </div>
                </div>
                
                <!-- Additional Notes -->
                ' . (!empty($payment['notes']) ? '<div class="section">
                    <div class="section-title">Additional Notes</div>
                    <p>' . htmlspecialchars($payment['notes']) . '</p>
                </div>' : '') . '
            </div>
            
            <div class="footer">
                <p><strong>Thank you for your payment!</strong></p>
                <p>This receipt has been generated automatically by the Library Management System.</p>
                <div class="footer-note">
                    Please keep this receipt for your records. If you have any queries regarding this payment, 
                    please contact the library administration.
                </div>
            </div>
        </div>
    </body>
    </html>';
    
    return $html;
}

/**
 * Download payment receipt as PDF
 * Uses browser print to PDF functionality
 */
function download_payment_receipt_pdf($payment_id, $conn) {
    require_once 'db_connect.php';
    
    // Fetch payment details
    $query = "SELECT p.*, s.name as student_name, s.email, s.phone, s.id as student_id
              FROM payments p
              JOIN students s ON p.student_id = s.id
              WHERE p.id = '$payment_id'";
    
    $result = mysqli_query($conn, $query);
    
    if (!$result || mysqli_num_rows($result) == 0) {
        return false;
    }
    
    $payment = mysqli_fetch_assoc($result);
    $student = [
        'id' => $payment['student_id'],
        'name' => $payment['student_name'],
        'email' => $payment['email'],
        'phone' => $payment['phone']
    ];
    
    $html = generate_payment_receipt_html($payment, $student);
    
    return $html;
}

?>
