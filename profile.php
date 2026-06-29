<?php
/**
 * FALCONS Theater - User Profile & Booking History
 */

session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/utils.php';
require_once __DIR__ . '/mailer.php';

requireLogin();

$userId = intval($_SESSION['user_id']);
$username = $_SESSION['username'] ?? 'User';
$message = '';
$messageType = 'info';

// Fetch user details
$userStmt = $conn->prepare('SELECT id, username, email FROM clients WHERE id = ? LIMIT 1');
$userStmt->bind_param('i', $userId);
$userStmt->execute();
$userResult = $userStmt->get_result();
$user = $userResult->fetch_assoc();
$userStmt->close();

if (!$user) {
    redirectTo('login.php');
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = sanitizeInput($_POST['action']);
    
    if ($action === 'update_profile' && verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $newUsername = sanitizeInput($_POST['username'] ?? '');
        $newEmail = sanitizeInput($_POST['email'] ?? '');
        
        // Validation
        if (empty($newUsername) || strlen($newUsername) < 3) {
            $message = 'Username must be at least 3 characters.';
            $messageType = 'error';
        } elseif (empty($newEmail) || !validateEmail($newEmail)) {
            $message = 'Please enter a valid email address.';
            $messageType = 'error';
        } else {
            // Update user
            $updateStmt = $conn->prepare('UPDATE clients SET username = ?, email = ? WHERE id = ?');
            $updateStmt->bind_param('ssi', $newUsername, $newEmail, $userId);
            
            if ($updateStmt->execute()) {
                $_SESSION['username'] = $newUsername;
                $message = 'Profile updated successfully!';
                $messageType = 'success';
                // Refresh user data
                $userStmt = $conn->prepare('SELECT id, username, email FROM clients WHERE id = ? LIMIT 1');
                $userStmt->bind_param('i', $userId);
                $userStmt->execute();
                $userResult = $userStmt->get_result();
                $user = $userResult->fetch_assoc();
                $userStmt->close();
            } else {
                $message = 'Error updating profile. Please try again.';
                $messageType = 'error';
            }
            $updateStmt->close();
        }
    }
}

// Fetch user's bookings
$bookingsStmt = $conn->prepare(
    'SELECT id, name, phone, movie, theater, seats, totalAmount, time FROM bookings WHERE phone = (SELECT SUBSTRING_INDEX(phone, " ", 1) FROM clients WHERE id = ? LIMIT 1) OR name = ? ORDER BY time DESC LIMIT 50'
);
$bookingsStmt->bind_param('is', $userId, $user['username']);
$bookingsStmt->execute();
$bookingsResult = $bookingsStmt->get_result();
$bookings = [];
while ($booking = $bookingsResult->fetch_assoc()) {
    $bookings[] = $booking;
}
$bookingsStmt->close();

// Fetch payment history
$paymentsStmt = $conn->prepare(
    'SELECT id, name, movie, theater, seats, totalAmount, cash_given, balance, payment_method, payment_status, time FROM payments WHERE name = ? ORDER BY time DESC LIMIT 50'
);
$paymentsStmt->bind_param('s', $user['username']);
$paymentsStmt->execute();
$paymentsResult = $paymentsStmt->get_result();
$payments = [];
while ($payment = $paymentsResult->fetch_assoc()) {
    $payments[] = $payment;
}
$paymentsStmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - FALCONS Theater</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="theme.css">
    <style>
        .profile-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .profile-header {
            background: linear-gradient(135deg, #f3b61f 0%, #ffd975 100%);
            color: #333;
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 30px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .profile-header h1 {
            margin: 0;
            font-size: 28px;
        }

        .profile-header p {
            margin: 5px 0 0 0;
            opacity: 0.8;
        }

        .tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            border-bottom: 2px solid rgba(255,255,255,0.1);
            flex-wrap: wrap;
        }

        .tab-button {
            padding: 12px 20px;
            border: none;
            background: transparent;
            color: rgba(255,255,255,0.7);
            cursor: pointer;
            font-size: 16px;
            border-bottom: 3px solid transparent;
            transition: all 0.3s;
        }

        .tab-button.active {
            color: #ffd975;
            border-bottom-color: #ffd975;
        }

        .tab-button:hover {
            color: #fff;
        }

        .tab-content {
            display: none;
            animation: fadeIn 0.3s;
        }

        .tab-content.active {
            display: block;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #ffd975;
        }

        .form-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid rgba(255,255,255,0.2);
            background: rgba(255,255,255,0.1);
            color: #fff;
            border-radius: 5px;
            font-size: 14px;
        }

        .form-group input:focus {
            outline: none;
            border-color: #ffd975;
            background: rgba(255,255,255,0.15);
        }

        .form-group input::placeholder {
            color: rgba(255,255,255,0.5);
        }

        .btn-submit {
            padding: 12px 30px;
            background: #f3b61f;
            color: #333;
            border: none;
            border-radius: 5px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-submit:hover {
            background: #ffd975;
            transform: translateY(-2px);
        }

        .alert {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            animation: slideDown 0.3s;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .alert-success {
            background: rgba(52, 211, 153, 0.2);
            border: 1px solid #34d399;
            color: #a7f3d0;
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.2);
            border: 1px solid #ef4444;
            color: #fca5a5;
        }

        .table-responsive {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        th {
            background: rgba(243, 182, 31, 0.2);
            color: #ffd975;
            padding: 12px;
            text-align: left;
            font-weight: 600;
        }

        td {
            padding: 12px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            color: rgba(255,255,255,0.8);
        }

        tr:hover {
            background: rgba(255,255,255,0.05);
        }

        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .status-paid {
            background: rgba(52, 211, 153, 0.3);
            color: #a7f3d0;
        }

        .status-pending {
            background: rgba(251, 191, 36, 0.3);
            color: #fcd34d;
        }

        .status-failed {
            background: rgba(239, 68, 68, 0.3);
            color: #fca5a5;
        }

        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: rgba(255,255,255,0.6);
        }

        .empty-state-icon {
            font-size: 48px;
            margin-bottom: 15px;
            opacity: 0.5;
        }

        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: #ffd975;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
        }

        .back-link:hover {
            color: #fff;
            margin-left: 5px;
        }
    </style>
</head>
<body class="theme-body">
    <div class="profile-container">
        <a href="index.php" class="back-link">← Back to Home</a>

        <div class="profile-header">
            <h1>My Profile</h1>
            <p>Manage your account and view booking history</p>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?= $messageType ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <div class="tabs">
            <button class="tab-button active" onclick="switchTab('profile')">
                <i class="fas fa-user"></i> Profile
            </button>
            <button class="tab-button" onclick="switchTab('bookings')">
                <i class="fas fa-ticket-alt"></i> My Bookings
            </button>
            <button class="tab-button" onclick="switchTab('payments')">
                <i class="fas fa-credit-card"></i> Payment History
            </button>
        </div>

        <!-- Profile Tab -->
        <div id="profile" class="tab-content active">
            <div class="glass-panel panel-padding">
                <h2>Account Information</h2>
                
                <form method="POST" class="form-section">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generateCSRFToken()) ?>">
                    <input type="hidden" name="action" value="update_profile">

                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" id="username" name="username" value="<?= htmlspecialchars($user['username']) ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
                    </div>

                    <button type="submit" class="btn-submit">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                </form>
            </div>
        </div>

        <!-- Bookings Tab -->
        <div id="bookings" class="tab-content">
            <div class="glass-panel panel-padding">
                <h2>My Bookings</h2>
                
                <?php if (count($bookings) > 0): ?>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Movie</th>
                                    <th>Theater</th>
                                    <th>Seats</th>
                                    <th>Amount</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($bookings as $booking): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($booking['movie']) ?></td>
                                        <td><?= htmlspecialchars($booking['theater'] ?? 'T1 - 4K') ?></td>
                                        <td><?= htmlspecialchars($booking['seats']) ?></td>
                                        <td><?= formatCurrency($booking['totalAmount']) ?></td>
                                        <td><?= formatDateTime($booking['time']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-state-icon"><i class="fas fa-inbox"></i></div>
                        <p>No bookings yet. <a href="index.php" style="color: #ffd975;">Browse movies and make your first booking!</a></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Payments Tab -->
        <div id="payments" class="tab-content">
            <div class="glass-panel panel-padding">
                <h2>Payment History</h2>
                
                <?php if (count($payments) > 0): ?>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Movie</th>
                                    <th>Theater</th>
                                    <th>Amount</th>
                                    <th>Method</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($payments as $payment): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($payment['movie']) ?></td>
                                        <td><?= htmlspecialchars($payment['theater'] ?? 'T1 - 4K') ?></td>
                                        <td><?= formatCurrency($payment['totalAmount']) ?></td>
                                        <td>
                                            <i class="fas fa-<?php 
                                                echo ($payment['payment_method'] === 'upi') ? 'mobile-alt' : 
                                                    (($payment['payment_method'] === 'card') ? 'credit-card' : 'money-bill'); 
                                            ?>"></i>
                                            <?= ucfirst(htmlspecialchars($payment['payment_method'])) ?>
                                        </td>
                                        <td>
                                            <span class="status-badge status-<?= htmlspecialchars($payment['payment_status']) ?>">
                                                <?= ucfirst(htmlspecialchars($payment['payment_status'])) ?>
                                            </span>
                                        </td>
                                        <td><?= formatDateTime($payment['time']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-state-icon"><i class="fas fa-credit-card"></i></div>
                        <p>No payment history yet.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        function switchTab(tabName) {
            // Hide all tabs
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });

            // Remove active class from all buttons
            document.querySelectorAll('.tab-button').forEach(btn => {
                btn.classList.remove('active');
            });

            // Show selected tab
            document.getElementById(tabName).classList.add('active');
            
            // Add active class to clicked button
            event.target.closest('.tab-button').classList.add('active');
        }
    </script>
</body>
</html>
