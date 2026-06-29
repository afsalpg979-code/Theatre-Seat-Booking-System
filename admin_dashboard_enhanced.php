<?php
/**
 * FALCONS Theater - Enhanced Admin Dashboard
 */

session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/utils.php';

requireAdminLogin();

$adminName = $_SESSION['admin_username'] ?? 'Admin';

// Pagination
$page = intval($_GET['page'] ?? 1);
$pageSize = 20;
$offset = ($page - 1) * $pageSize;

// Get total counts
$totalBookings = dbCount($conn, 'bookings');
$totalPayments = dbCount($conn, 'payments');
$totalClients = dbCount($conn, 'clients');
$totalRatings = dbCount($conn, 'ratings');

$totalPages = ceil($totalBookings / $pageSize);

// Fetch bookings with pagination
$bookings = dbFetchAll($conn, 
    "SELECT id, name, phone, movie, theater, seats, totalAmount, time 
     FROM bookings 
     ORDER BY time DESC 
     LIMIT ? OFFSET ?", 
    [$pageSize, $offset], 
    'ii'
);

// Fetch recent payments
$recentPayments = dbFetchAll($conn,
    "SELECT id, name, movie, theater, totalAmount, payment_status, payment_method, time
     FROM payments
     ORDER BY time DESC
     LIMIT 10"
);

// Handle delete
if (isset($_GET['delete_id']) && verifyCSRFToken($_POST['csrf_token'] ?? $_GET['csrf_token'] ?? '')) {
    $deleteId = intval($_GET['delete_id']);
    if (dbDelete($conn, 'bookings', $deleteId)) {
        redirectWithMessage('admin_dashboard_enhanced.php', 'Booking deleted successfully', 'success');
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - FALCONS THEATER</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --bg-dark: #07131d;
            --bg-mid: #10273a;
            --accent: #f3b61f;
            --accent-soft: #ffd975;
            --text-main: #f6fbff;
            --text-soft: #c2d6e5;
            --highlight: #15c39a;
            --danger: #ef4444;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, var(--bg-dark) 0%, var(--bg-mid) 55%, #091621 100%);
            color: var(--text-main);
            min-height: 100vh;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 40px;
            padding: 20px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .header h1 {
            font-size: 28px;
            color: var(--accent-soft);
        }

        .header-actions {
            display: flex;
            gap: 15px;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            background: var(--accent);
            color: #333;
        }

        .btn-primary:hover {
            background: var(--accent-soft);
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: transparent;
            border: 1px solid var(--accent);
            color: var(--accent);
        }

        .btn-secondary:hover {
            background: rgba(243, 182, 31, 0.1);
        }

        .btn-danger {
            background: var(--danger);
            color: white;
        }

        .btn-danger:hover {
            background: #dc2626;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            transition: all 0.3s;
        }

        .stat-card:hover {
            background: rgba(255, 255, 255, 0.12);
            border-color: var(--accent);
        }

        .stat-icon {
            font-size: 32px;
            margin-bottom: 10px;
            color: var(--accent);
        }

        .stat-value {
            font-size: 28px;
            font-weight: 700;
            color: var(--accent-soft);
            margin-bottom: 5px;
        }

        .stat-label {
            color: var(--text-soft);
            font-size: 12px;
            text-transform: uppercase;
        }

        .section {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 30px;
        }

        .section-title {
            font-size: 20px;
            color: var(--accent-soft);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .table-responsive {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background: rgba(243, 182, 31, 0.2);
            color: var(--accent-soft);
            padding: 12px;
            text-align: left;
            font-weight: 600;
            border-bottom: 2px solid rgba(243, 182, 31, 0.3);
        }

        td {
            padding: 12px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        tr:hover {
            background: rgba(255, 255, 255, 0.05);
        }

        .truncate {
            max-width: 200px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .status-paid {
            background: rgba(21, 195, 154, 0.3);
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

        .action-buttons {
            display: flex;
            gap: 10px;
        }

        .btn-small {
            padding: 6px 12px;
            font-size: 12px;
        }

        .pagination {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 20px;
            align-items: center;
        }

        .pagination a,
        .pagination span {
            padding: 8px 12px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 5px;
            text-decoration: none;
            color: var(--text-main);
            transition: all 0.3s;
        }

        .pagination a:hover {
            background: var(--accent);
            color: #333;
            border-color: var(--accent);
        }

        .pagination .active {
            background: var(--accent);
            color: #333;
            border-color: var(--accent);
        }

        .empty-state {
            text-align: center;
            padding: 40px;
            color: var(--text-soft);
        }

        .empty-state-icon {
            font-size: 48px;
            margin-bottom: 15px;
            opacity: 0.5;
        }

        .quick-links {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
        }

        .quick-link {
            background: rgba(243, 182, 31, 0.1);
            border: 1px solid rgba(243, 182, 31, 0.3);
            border-radius: 5px;
            padding: 15px;
            text-align: center;
            text-decoration: none;
            color: var(--accent-soft);
            transition: all 0.3s;
        }

        .quick-link:hover {
            background: rgba(243, 182, 31, 0.2);
            border-color: var(--accent);
        }

        .alert {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        .alert-success {
            background: rgba(21, 195, 154, 0.2);
            border: 1px solid #34d399;
            color: #a7f3d0;
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.2);
            border: 1px solid #ef4444;
            color: #fca5a5;
        }

        .logout-btn {
            background: var(--danger);
            color: white;
        }

        .logout-btn:hover {
            background: #dc2626;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div>
                <h1><i class="fas fa-film"></i> FALCONS Admin Dashboard</h1>
                <p style="color: var(--text-soft); margin-top: 5px;">Signed in as <strong><?= htmlspecialchars($adminName) ?></strong></p>
            </div>
            <div class="header-actions">
                <a href="AdminOnly.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Main Admin
                </a>
                <a href="admin_login.php?logout=1" class="btn logout-btn">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>

        <!-- Stats Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-ticket-alt"></i></div>
                <div class="stat-value"><?= $totalBookings ?></div>
                <div class="stat-label">Total Bookings</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-credit-card"></i></div>
                <div class="stat-value"><?= $totalPayments ?></div>
                <div class="stat-label">Total Payments</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-users"></i></div>
                <div class="stat-value"><?= $totalClients ?></div>
                <div class="stat-label">Total Clients</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-star"></i></div>
                <div class="stat-value"><?= $totalRatings ?></div>
                <div class="stat-label">Total Ratings</div>
            </div>
        </div>

        <!-- Quick Links -->
        <div class="section">
            <div class="section-title">
                <i class="fas fa-link"></i> Quick Links
            </div>
            <div class="quick-links">
                <a href="upcoming.php" class="quick-link">
                    <i class="fas fa-film"></i> Add Movies
                </a>
                <a href="customer.php" class="quick-link">
                    <i class="fas fa-envelope"></i> Messages
                </a>
                <a href="rating.php" class="quick-link">
                    <i class="fas fa-star"></i> Ratings
                </a>
                <a href="ClientDetail.php" class="quick-link">
                    <i class="fas fa-users"></i> Clients
                </a>
            </div>
        </div>

        <!-- Bookings Table -->
        <div class="section">
            <div class="section-title">
                <i class="fas fa-ticket-alt"></i> Recent Bookings
            </div>
            
            <?php if (count($bookings) > 0): ?>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Customer Name</th>
                                <th>Phone</th>
                                <th>Movie</th>
                                <th>Theater</th>
                                <th>Seats</th>
                                <th>Amount</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($bookings as $booking): ?>
                                <tr>
                                    <td><?= htmlspecialchars($booking['id']) ?></td>
                                    <td><?= htmlspecialchars($booking['name']) ?></td>
                                    <td><?= htmlspecialchars($booking['phone']) ?></td>
                                    <td class="truncate"><?= htmlspecialchars($booking['movie']) ?></td>
                                    <td><?= htmlspecialchars($booking['theater'] ?? 'T1 - 4K') ?></td>
                                    <td><?= htmlspecialchars($booking['seats']) ?></td>
                                    <td><?= formatCurrency($booking['totalAmount']) ?></td>
                                    <td><?= formatDateTime($booking['time']) ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="print1.php?id=<?= $booking['id'] ?>" class="btn btn-primary btn-small" target="_blank">
                                                <i class="fas fa-print"></i> Print
                                            </a>
                                            <a href="?delete_id=<?= $booking['id'] ?>" class="btn btn-danger btn-small" onclick="return confirm('Delete this booking?');">
                                                <i class="fas fa-trash"></i> Delete
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=1">First</a>
                        <a href="?page=<?= $page - 1 ?>">Previous</a>
                    <?php endif; ?>

                    <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                        <?php if ($i === $page): ?>
                            <span class="active"><?= $i ?></span>
                        <?php else: ?>
                            <a href="?page=<?= $i ?>"><?= $i ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>

                    <?php if ($page < $totalPages): ?>
                        <a href="?page=<?= $page + 1 ?>">Next</a>
                        <a href="?page=<?= $totalPages ?>">Last</a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-state-icon"><i class="fas fa-inbox"></i></div>
                    <p>No bookings found.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Recent Payments -->
        <div class="section">
            <div class="section-title">
                <i class="fas fa-credit-card"></i> Recent Payments
            </div>
            
            <?php if (count($recentPayments) > 0): ?>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Movie</th>
                                <th>Theater</th>
                                <th>Amount</th>
                                <th>Method</th>
                                <th>Status</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentPayments as $payment): ?>
                                <tr>
                                    <td><?= htmlspecialchars($payment['id']) ?></td>
                                    <td><?= htmlspecialchars($payment['name']) ?></td>
                                    <td class="truncate"><?= htmlspecialchars($payment['movie']) ?></td>
                                    <td><?= htmlspecialchars($payment['theater'] ?? 'T1 - 4K') ?></td>
                                    <td><?= formatCurrency($payment['totalAmount']) ?></td>
                                    <td><?= ucfirst(htmlspecialchars($payment['payment_method'])) ?></td>
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
                    <p>No payments found.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
