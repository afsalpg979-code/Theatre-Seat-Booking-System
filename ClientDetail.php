<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "cinema_db";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (isset($_GET['delete_id'])) {
    $id = intval($_GET['delete_id']);
    $stmt = $conn->prepare("DELETE FROM clients WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();

    header("Location: ClientDetail.php");
    exit();
}

$sql = "SELECT id, username, email FROM clients ORDER BY id DESC";
$result = $conn->query($sql);
$clientCount = $result ? $result->num_rows : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Client Details - FALCONS THEATER</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; }

        :root {
            --bg-dark: #07131d;
            --bg-mid: #10273a;
            --panel: #ffffff;
            --panel-soft: #f6f8fb;
            --text: #17202a;
            --muted: #64748b;
            --line: #e6ebf2;
            --brand: #15c39a;
            --brand-dark: #0f8d72;
            --danger: #dc2626;
            --danger-soft: #fee2e2;
            --gold: #f3b61f;
        }

        body {
            font-family: 'Poppins', Arial, sans-serif;
            min-height: 100vh;
            margin: 0;
            padding: 32px 18px;
            background:
                radial-gradient(circle at top left, rgba(21, 195, 154, 0.2), transparent 28rem),
                linear-gradient(135deg, var(--bg-dark) 0%, var(--bg-mid) 58%, #091621 100%);
            color: var(--text);
        }

        .container { max-width: 1180px; margin: 0 auto; }

        .dashboard-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 18px;
            margin-bottom: 22px;
            color: #fff;
        }

        .eyebrow {
            display: inline-block;
            margin-bottom: 8px;
            color: var(--gold);
            font-size: 0.78rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        h1 {
            margin: 0;
            font-size: clamp(1.7rem, 4vw, 2.7rem);
            line-height: 1.1;
        }

        .header-copy {
            max-width: 640px;
            margin: 10px 0 0;
            color: #c2d6e5;
            font-size: 0.96rem;
        }

        .summary-pill {
            min-width: 150px;
            padding: 16px 18px;
            border: 1px solid rgba(255,255,255,0.16);
            border-radius: 8px;
            background: rgba(255,255,255,0.08);
            text-align: center;
            box-shadow: 0 16px 40px rgba(0,0,0,0.18);
        }

        .summary-pill strong { display: block; font-size: 2rem; line-height: 1; }
        .summary-pill span { display: block; margin-top: 6px; color: #c2d6e5; font-size: 0.82rem; font-weight: 500; }

        .table-card {
            overflow: hidden;
            border-radius: 8px;
            background: var(--panel);
            box-shadow: 0 24px 70px rgba(0,0,0,0.28);
        }

        .table-toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 14px;
            padding: 18px 20px;
            border-bottom: 1px solid var(--line);
            background: var(--panel-soft);
        }

        .table-toolbar h2 { margin: 0; font-size: 1rem; }
        .table-toolbar p { margin: 4px 0 0; color: var(--muted); font-size: 0.86rem; }
        .table-wrap { width: 100%; overflow-x: auto; }

        table {
            width: 100%;
            min-width: 720px;
            border-collapse: collapse;
            background: #fff;
        }

        th, td {
            padding: 15px 16px;
            border-bottom: 1px solid var(--line);
            text-align: left;
            vertical-align: middle;
        }

        th {
            background: #0f2436;
            color: #eef7ff;
            font-size: 0.76rem;
            font-weight: 700;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            white-space: nowrap;
        }

        td { color: #253244; font-size: 0.92rem; }
        td:first-child { width: 70px; color: var(--muted); font-weight: 600; }

        .client-name {
            color: #0f4f91;
            font-weight: 700;
        }

        tr:nth-child(even) { background: #fafcff; }
        tr:hover { background: #eef6ff; }

        .delete-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 34px;
            padding: 8px 13px;
            border: 1px solid #fecaca;
            border-radius: 6px;
            background: var(--danger-soft);
            color: var(--danger);
            text-decoration: none;
            font-size: 0.82rem;
            font-weight: 700;
            transition: background 0.2s, color 0.2s, border-color 0.2s;
        }

        .delete-btn:hover {
            background: var(--danger);
            border-color: var(--danger);
            color: #fff;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: fit-content;
            margin: 22px 0 0;
            padding: 11px 18px;
            background: var(--brand);
            color: #fff;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 700;
            transition: background 0.2s, transform 0.2s;
        }

        .back-link:hover { background: var(--brand-dark); transform: translateY(-1px); }

        .empty-state {
            padding: 46px 20px;
            text-align: center;
            color: var(--muted);
            background: var(--panel);
            border-radius: 8px;
            box-shadow: 0 24px 70px rgba(0,0,0,0.28);
        }

        @media (max-width: 720px) {
            body { padding: 22px 12px; }
            .dashboard-header, .table-toolbar { align-items: flex-start; flex-direction: column; }
            .summary-pill { width: 100%; text-align: left; }
        }
    </style>
</head>
<body>
<div class="container">
    <header class="dashboard-header">
        <div>
            <span class="eyebrow">Admin Users</span>
            <h1>Client Details</h1>
            <p class="header-copy">Review registered client accounts, usernames, and contact emails from one clean admin view.</p>
        </div>
        <div class="summary-pill">
            <strong><?= $clientCount ?></strong>
            <span>Total Clients</span>
        </div>
    </header>

    <?php if ($result && $result->num_rows > 0): ?>
        <section class="table-card">
            <div class="table-toolbar">
                <div>
                    <h2>Registered Clients</h2>
                    <p>Sorted by newest account first.</p>
                </div>
                <a href="AdminOnly.php" class="back-link">Back to Admin Home</a>
            </div>
            <div class="table-wrap">
                <table>
                    <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Action</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?= intval($row["id"]) ?></td>
                            <td class="client-name"><?= htmlspecialchars($row["username"]) ?></td>
                            <td><?= htmlspecialchars($row["email"]) ?></td>
                            <td>
                                <a class="delete-btn" href="ClientDetail.php?delete_id=<?= intval($row["id"]) ?>" onclick="return confirm('Are you sure you want to delete this client?');">Delete</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </section>
    <?php else: ?>
        <div class="empty-state">No clients found.</div>
        <a href="AdminOnly.php" class="back-link">Back to Admin Home</a>
    <?php endif; ?>
</div>

<?php $conn->close(); ?>
</body>
</html>
