<?php
session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin_login.php');
    exit();
}

include 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete_movie_id'])) {
        $movieId = intval($_POST['delete_movie_id']);
        $stmt = $conn->prepare("DELETE FROM upcoming_movies WHERE id = ?");
        $stmt->bind_param("i", $movieId);
        $stmt->execute();
        $stmt->close();

        header('Location: upcoming.php');
        exit();
    }

    if (isset($_POST['price_movie_id'], $_POST['price_action'])) {
        $movieId = intval($_POST['price_movie_id']);
        $priceAction = $_POST['price_action'];
        $step = 10.00;

        if ($priceAction === 'increase') {
            $stmt = $conn->prepare("UPDATE upcoming_movies SET ticket_price = ticket_price + ? WHERE id = ?");
            $stmt->bind_param("di", $step, $movieId);
            $stmt->execute();
            $stmt->close();
        } elseif ($priceAction === 'decrease') {
            $minimumPrice = 10.00;
            $stmt = $conn->prepare("UPDATE upcoming_movies SET ticket_price = GREATEST(ticket_price - ?, ?) WHERE id = ?");
            $stmt->bind_param("ddi", $step, $minimumPrice, $movieId);
            $stmt->execute();
            $stmt->close();
        }

        header('Location: upcoming.php');
        exit();
    }

    $title = trim($_POST['title']);
    $language = trim($_POST['language']);
    $image = trim($_POST['image']);
    $trailer_url = trim($_POST['trailer_url'] ?? '');
    $rating = trim($_POST['rating']);
    $votes = trim($_POST['votes']);
    $description = trim($_POST['description']);
    $release_date = trim($_POST['release_date']);
    $ticket_price = max(10, floatval($_POST['ticket_price'] ?? 100));

    $stmt = $conn->prepare(
        "INSERT INTO upcoming_movies (title, language, image, trailer_url, rating, votes, description, release_date, ticket_price)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );
    $stmt->bind_param("ssssssssd", $title, $language, $image, $trailer_url, $rating, $votes, $description, $release_date, $ticket_price);
    $stmt->execute();
    $stmt->close();

    header('Location: upcoming.php');
    exit();
}

$result = mysqli_query($conn, "SELECT * FROM upcoming_movies ORDER BY release_date ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Upcoming Movies - FALCONS Theater</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="theme.css">
<style>
    .price-control {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        margin-top: 14px;
        padding: 12px;
        border: 1px solid rgba(243, 182, 31, 0.22);
        border-radius: 8px;
        background: rgba(255, 255, 255, 0.06);
    }

    .price-value {
        color: #ffd975;
        font-size: 1.08rem;
        font-weight: 700;
        white-space: nowrap;
    }

    .price-actions {
        display: inline-flex;
        gap: 8px;
    }

    .price-button {
        width: 38px;
        height: 38px;
        border: 0;
        border-radius: 8px;
        background: #f3b61f;
        color: #07131d;
        cursor: pointer;
        font-size: 1.25rem;
        font-weight: 700;
        line-height: 1;
    }

    .price-button:hover {
        background: #ffd975;
    }

    .movie-admin-actions {
        display: flex;
        gap: 10px;
        margin-top: 12px;
    }

    .delete-movie-button {
        width: 100%;
        min-height: 40px;
        border: 1px solid rgba(255, 107, 107, 0.32);
        border-radius: 8px;
        background: rgba(255, 107, 107, 0.14);
        color: #ff6b6b;
        cursor: pointer;
        font: inherit;
        font-weight: 700;
    }

    .delete-movie-button:hover {
        background: #ff6b6b;
        color: #fff;
    }
</style>
</head>
<body class="theme-body">
<div class="page-shell">
    <section class="page-hero">
        <div class="hero-content">
            <span class="eyebrow">Admin Movie Control</span>
            <h1>Add, organize, and review upcoming releases in one place.</h1>
            <p class="hero-copy">This section helps admins maintain the upcoming slate with clean forms, visual previews, and a responsive list that works across devices.</p>
        </div>
    </section>

    <section class="content-grid grid-two">
        <div class="glass-panel surface-panel form-card">
            <h2>Add New Movie</h2>
            <p class="form-copy">Enter poster, language, release date, and short description to publish the movie into the upcoming list.</p>
            <form method="POST" class="field-grid">
                <input class="input" type="text" name="title" placeholder="Title" required>
                <select class="select" name="language" required>
                    <option value="">Select Language</option>
                    <option>Malayalam</option>
                    <option>English</option>
                    <option>Hindi</option>
                    <option>Tamil</option>
                </select>
                <input class="input" type="text" name="image" placeholder="Image URL" required>
                <input class="input" type="url" name="trailer_url" placeholder="Trailer URL (YouTube embed or watch link)">
                <div class="field-grid two-col">
                    <input class="input" type="text" name="rating" placeholder="Rating">
                    <input class="input" type="text" name="votes" placeholder="Votes">
                </div>
                <textarea class="textarea" name="description" placeholder="Description" required></textarea>
                <div class="field-grid two-col">
                    <input class="input" type="date" name="release_date" required>
                    <input class="input" type="number" name="ticket_price" min="10" step="10" value="100" placeholder="Ticket Price (Rs.)" required>
                </div>
                <div class="action-row">
                    <button class="btn" type="submit">Add Movie</button>
                    <a href="AdminOnly.php" class="btn-outline">Back to Admin Home</a>
                </div>
            </form>
        </div>

        <div class="glass-panel panel-padding stack">
            <h2 class="section-title">Upcoming Movie List</h2>
            <p class="intro-copy">Everything added here appears in a cleaner responsive list so admins can review posters and release timing at a glance.</p>

            <div class="movie-grid">
                <?php while ($movie = mysqli_fetch_assoc($result)): ?>
                    <div class="movie-tile">
                        <img class="movie-poster" src="<?= htmlspecialchars($movie['image']); ?>" alt="<?= htmlspecialchars($movie['title']); ?>">
                        <div class="inner">
                            <h3><?= htmlspecialchars($movie['title']); ?></h3>
                            <p class="movie-meta"><strong>Language:</strong> <?= htmlspecialchars($movie['language']); ?></p>
                            <p class="movie-meta"><strong>Rating:</strong> <?= htmlspecialchars($movie['rating']); ?> | <?= htmlspecialchars($movie['votes']); ?></p>
                            <?php if (!empty($movie['trailer_url'])): ?>
                                <p class="movie-meta"><strong>Trailer:</strong> <a href="<?= htmlspecialchars($movie['trailer_url']); ?>" target="_blank" rel="noopener">Open trailer</a></p>
                            <?php endif; ?>
                            <p class="movie-meta"><?= htmlspecialchars($movie['description']); ?></p>
                            <p class="movie-meta"><strong>Releasing:</strong> <?= htmlspecialchars($movie['release_date']); ?></p>
                            <div class="price-control">
                                <span class="price-value">Rs.<?= number_format((float) ($movie['ticket_price'] ?? 100), 2); ?></span>
                                <form method="POST" class="price-actions">
                                    <input type="hidden" name="price_movie_id" value="<?= intval($movie['id']); ?>">
                                    <button class="price-button" type="submit" name="price_action" value="decrease" aria-label="Decrease ticket price">-</button>
                                    <button class="price-button" type="submit" name="price_action" value="increase" aria-label="Increase ticket price">+</button>
                                </form>
                            </div>
                            <form method="POST" class="movie-admin-actions" onsubmit="return confirm('Delete this movie?');">
                                <input type="hidden" name="delete_movie_id" value="<?= intval($movie['id']); ?>">
                                <button class="delete-movie-button" type="submit">Delete Movie</button>
                            </form>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
    </section>
</div>
</body>
</html>
