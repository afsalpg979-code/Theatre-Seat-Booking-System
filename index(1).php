<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

include 'config.php';

function getTrailerEmbedUrl($url)
{
    $url = trim((string) $url);
    if ($url === '') {
        return '';
    }

    $parts = parse_url($url);
    if ($parts === false || empty($parts['host'])) {
        return '';
    }

    $host = strtolower($parts['host']);
    $path = $parts['path'] ?? '';

    if (strpos($host, 'youtube.com') !== false) {
        if (strpos($path, '/embed/') === 0) {
            return $url;
        }

        parse_str($parts['query'] ?? '', $query);
        if (!empty($query['v'])) {
            return 'https://www.youtube.com/embed/' . rawurlencode($query['v']);
        }

        if (strpos($path, '/shorts/') === 0) {
            return 'https://www.youtube.com/embed/' . rawurlencode(basename($path));
        }
    }

    if (strpos($host, 'youtu.be') !== false) {
        $videoId = trim($path, '/');
        if ($videoId !== '') {
            return 'https://www.youtube.com/embed/' . rawurlencode($videoId);
        }
    }

    return $url;
}

$movies = [];
if ($table_check && mysqli_num_rows($table_check) > 0) {
    $query = "SELECT * FROM upcoming_movies WHERE release_date <= CURDATE() ORDER BY release_date DESC";
    $result = mysqli_query($conn, $query);
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $movies[] = $row;
        }
    }
}

$username = $_SESSION['username'] ?? 'User';
$userInitial = strtoupper(substr($username, 0, 1));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>FALCONS Theater - Now Showing</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            background: linear-gradient(rgba(0, 0, 0, 0.75), rgba(0, 0, 0, 0.75)),
                        url('https://images.unsplash.com/photo-1524985069026-dd778a71c7b4?auto=format&fit=crop&w=1950&q=80') no-repeat center center fixed;
            background-size: cover;
            color: #fff;
        }
        header {
            background-color: rgba(0, 0, 0, 0.7);
            color: white;
            padding: 20px;
            position: relative;
            text-align: center;
        }
        .menu-button {
            font-size: 26px;
            cursor: pointer;
            position: absolute;
            left: 20px;
            top: 20px;
        }
        .user-menu {
            position: absolute;
            top: 18px;
            right: 20px;
            z-index: 1100;
        }
        .user-avatar {
            width: 46px;
            height: 46px;
            border-radius: 50%;
            border: 2px solid #FFD700;
            background: linear-gradient(135deg, #FFD700, #ff9f1c);
            color: #111;
            font-weight: 700;
            font-size: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(0,0,0,0.25);
        }
        .user-dropdown {
            display: none;
            position: absolute;
            right: 0;
            top: 56px;
            min-width: 200px;
            background: #fff;
            color: #222;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.25);
            overflow: hidden;
            z-index: 1050;
        }
        .user-dropdown.show {
            display: block;
        }
        .user-info {
            padding: 14px 16px;
            border-bottom: 1px solid #eee;
            font-size: 14px;
            font-weight: 600;
        }
        .user-menu-links {
            display: flex;
            flex-direction: column;
        }
        .user-menu-link {
            display: block;
            padding: 12px 16px;
            background: #fff;
            color: #333;
            text-decoration: none;
            border-bottom: 1px solid #eee;
            font-size: 14px;
            transition: all 0.3s;
        }
        .user-menu-link:last-child {
            border-bottom: none;
        }
        .user-menu-link:hover {
            background: #f5f5f5;
        }
        .logout-btn {
            color: #d62828;
            font-weight: 600;
        }
        .logout-btn:hover {
            background: #fff4f4;
        }
        .sidebar {
            height: 100%;
            width: 250px;
            background-color: #111;
            position: fixed;
            top: 0;
            left: -250px;
            padding-top: 60px;
            transition: 0.3s;
            z-index: 1000;
        }
        .sidebar.show { left: 0; }
        .sidebar h2 { text-align: center; margin-bottom: 20px; color: #FFD700; }
        .sidebar a {
            display: block;
            padding: 12px 20px;
            color: #fff;
            text-decoration: none;
            border-bottom: 1px solid #444;
        }
        .sidebar a:hover { background-color: #444; }
        .sidebar .close-btn {
            position: absolute;
            top: 15px;
            right: 20px;
            font-size: 24px;
            color: white;
            cursor: pointer;
            background: none;
            border: none;
        }
        .container { padding: 30px 20px; }
        .language-filter {
            text-align: center;
            margin-bottom: 30px;
        }
        .language-filter button {
            padding: 10px 20px;
            margin: 5px;
            font-size: 16px;
            border: none;
            border-radius: 5px;
            background-color: #444;
            color: #fff;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        .language-filter button.active,
        .language-filter button:hover {
            background-color: #FFD700;
            color: black;
        }
        .movies {
            display: flex;
            flex-wrap: wrap;
            gap: 25px;
            justify-content: center;
        }
        .movie-card {
            width: 250px;
            background-color: rgba(255, 255, 255, 0.08);
            border-radius: 12px;
            overflow: hidden;
            backdrop-filter: blur(4px);
            text-align: center;
            box-shadow: 0 4px 15px rgba(0,0,0,0.4);
            transition: transform 0.3s;
        }
        .movie-card:hover { transform: scale(1.03); }
        .movie-card img {
            width: 100%;
            height: 350px;
            object-fit: cover;
        }
        .movie-card .details { padding: 15px; color: #fff; }
        .movie-card .details h3 { font-size: 18px; margin-bottom: 10px; color: #FFD700; }
        .movie-card .details p { font-size: 14px; margin: 5px 0; }
        .movie-card .book-btn {
            display: inline-block;
            background-color: #FFD700;
            color: black;
            padding: 8px 16px;
            border-radius: 5px;
            text-decoration: none;
            margin-top: 10px;
        }
        .movie-card .book-btn:hover { background-color: #ffcc00; }
        .movie-actions {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 8px;
            margin-top: 12px;
        }
        .movie-card .trailer-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            background-color: transparent;
            color: #FFD700;
            padding: 8px 14px;
            border: 1px solid rgba(255, 215, 0, 0.65);
            border-radius: 5px;
            cursor: pointer;
            font: inherit;
            text-decoration: none;
        }
        .movie-card .trailer-btn:hover {
            background-color: rgba(255, 215, 0, 0.14);
        }
        .trailer-modal {
            display: none;
            position: fixed;
            inset: 0;
            z-index: 2000;
            background: rgba(0, 0, 0, 0.84);
            padding: 24px;
            align-items: center;
            justify-content: center;
        }
        .trailer-modal.show {
            display: flex;
        }
        .trailer-dialog {
            width: min(920px, 100%);
            background: #101010;
            border: 1px solid rgba(255, 215, 0, 0.3);
            border-radius: 10px;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.45);
            overflow: hidden;
        }
        .trailer-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            padding: 14px 18px;
            background: #181818;
        }
        .trailer-title {
            margin: 0;
            color: #FFD700;
            font-size: 18px;
        }
        .trailer-close {
            width: 38px;
            height: 38px;
            border: 0;
            border-radius: 50%;
            background: #FFD700;
            color: #111;
            cursor: pointer;
            font-size: 22px;
            line-height: 1;
        }
        .trailer-frame-wrap {
            width: 100%;
            aspect-ratio: 16 / 9;
            background: #000;
        }
        .trailer-frame {
            width: 100%;
            height: 100%;
            border: 0;
            display: block;
        }
        footer {
            background-color: rgba(0,0,0,0.8);
            color: white;
            padding: 20px;
            text-align: center;
        }
        .map-btn {
            background-color: #28a745;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin-top: 10px;
        }
        .map-btn i { margin-right: 8px; }
        @media (max-width: 900px) {
            header {
                padding: 72px 20px 20px;
            }
            .user-menu {
                top: 14px;
                right: 16px;
            }
            .menu-button {
                top: 16px;
                left: 16px;
            }
        }
        @media (max-width: 700px) {
            .sidebar {
                width: 220px;
                left: -220px;
            }
            .container {
                padding: 24px 14px 32px;
            }
            .movies {
                gap: 18px;
            }
            .movie-card {
                width: min(100%, 320px);
            }
        }
        @media (max-width: 560px) {
            .language-filter {
                display: flex;
                flex-wrap: wrap;
                justify-content: center;
            }
            .language-filter button {
                flex: 1 1 42%;
                min-width: 120px;
            }
            .movie-card img {
                height: 300px;
            }
            footer {
                padding: 18px 14px 28px;
            }
            .map-btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>

<header>
    <span class="menu-button" onclick="toggleSidebar()">&#9776;</span>
    <div class="user-menu">
        <div class="user-avatar" id="userAvatar"><?= htmlspecialchars($userInitial) ?></div>
        <div class="user-dropdown" id="userDropdown">
            <div class="user-info"><?= htmlspecialchars($username) ?></div>
            <div class="user-menu-links">
                <a href="profile.php" class="user-menu-link">
                    <i class="fas fa-user"></i> My Profile
                </a>
                <a href="profile.php?tab=bookings" class="user-menu-link">
                    <i class="fas fa-ticket-alt"></i> My Bookings
                </a>
                <a href="profile.php?tab=payments" class="user-menu-link">
                    <i class="fas fa-credit-card"></i> Payments
                </a>
                <a href="login.php?logout=1" class="user-menu-link logout-btn">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </div>
    <h1>FALCONS Theater - Now Showing</h1>
</header>

<div class="sidebar" id="sidebar">
    <button class="close-btn" onclick="toggleSidebar()">X</button>
    <h2>FALCONS MOVIES</h2>
    <a href="index.php">Movies</a>
    <a href="profile.php">My Profile</a>
    <a href="Rate.php">Rating</a>
    <a href="contact.php">Contact</a>
    <a href="about.php">About Us</a>
    <a href="admin_login.php">Admin Login</a>
</div>

<div class="container">
    <div class="language-filter">
        <button id="all-btn" class="active">All</button>
        <button id="malayalam-btn">Malayalam</button>
        <button id="english-btn">English</button>
        <button id="hindi-btn">Hindi</button>
        <button id="tamil-btn">Tamil</button>
    </div>

    <h2 style="text-align:center;">Now Showing Movies</h2>

    <div class="movies" id="movie-list">
        <?php if (empty($movies)): ?>
            <p style="text-align:center; font-size:18px; color:#FFD700;">No movies released yet. Please check back soon!</p>
        <?php else: ?>
            <?php foreach ($movies as $movie): ?>
                <?php
                    $bookingPage = 'booking-confirmation.php';
                    if ($movie['title'] === 'Maleficent: Mistress of Evil') {
                        $bookingPage = 'booking-confirmation1.php';
                    } elseif ($movie['title'] === 'WALL-E') {
                        $bookingPage = 'booking-confirmation2.php';
                    } elseif ($movie['title'] === 'Meiyazhagan') {
                        $bookingPage = 'booking-confirmation3.php';
                    }
                    $trailerEmbedUrl = getTrailerEmbedUrl($movie['trailer_url'] ?? '');
                ?>
                <div class="movie-card <?= htmlspecialchars(strtolower($movie['language'])) ?>">
                    <img src="<?= htmlspecialchars($movie['image']) ?>" alt="<?= htmlspecialchars($movie['title']) ?>">
                    <div class="details">
                        <h3><?= htmlspecialchars($movie['title']) ?></h3>
                        <p>Rating: <?= htmlspecialchars($movie['rating']) ?> | Votes: <?= htmlspecialchars($movie['votes']) ?></p>
                        <p>Ticket Price: Rs.<?= number_format((float) ($movie['ticket_price'] ?? 100), 2) ?></p>
                        <p><?= htmlspecialchars($movie['description']) ?></p>
                        <div class="movie-actions">
                            <a href="<?= htmlspecialchars($bookingPage) ?>?movie=<?= urlencode($movie['title']) ?>" class="book-btn">Book Now</a>
                            <?php if ($trailerEmbedUrl !== ''): ?>
                                <button
                                    type="button"
                                    class="trailer-btn"
                                    data-trailer="<?= htmlspecialchars($trailerEmbedUrl) ?>"
                                    data-title="<?= htmlspecialchars($movie['title']) ?>">
                                    <i class="fas fa-play"></i> Trailer
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<div class="trailer-modal" id="trailerModal" aria-hidden="true">
    <div class="trailer-dialog" role="dialog" aria-modal="true" aria-labelledby="trailerTitle">
        <div class="trailer-header">
            <h2 class="trailer-title" id="trailerTitle">Movie Trailer</h2>
            <button type="button" class="trailer-close" id="trailerClose" aria-label="Close trailer">&times;</button>
        </div>
        <div class="trailer-frame-wrap">
            <iframe
                class="trailer-frame"
                id="trailerFrame"
                title="Movie trailer"
                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                allowfullscreen></iframe>
        </div>
    </div>
</div>

<footer>
    <p>&copy; 2025 FALCONS Theater. All Rights Reserved.</p>
    <button class="map-btn" onclick="window.location.href='map.html';">
        <i class="fas fa-map-marker-alt"></i> Find Us on Google Maps
    </button>
</footer>

<script>
    function toggleSidebar() {
        document.getElementById('sidebar').classList.toggle('show');
    }

    const userAvatar = document.getElementById('userAvatar');
    const userDropdown = document.getElementById('userDropdown');

    userAvatar.addEventListener('click', () => {
        userDropdown.classList.toggle('show');
    });

    document.addEventListener('click', (event) => {
        if (!event.target.closest('.user-menu')) {
            userDropdown.classList.remove('show');
        }
    });

    const filterButtons = document.querySelectorAll('.language-filter button');
    const movieCards = document.querySelectorAll('.movie-card');

    filterButtons.forEach(button => {
        button.addEventListener('click', () => {
            filterButtons.forEach(btn => btn.classList.remove('active'));
            button.classList.add('active');
            const selected = button.id.replace('-btn', '').toLowerCase();
            movieCards.forEach(card => {
                if (selected === 'all' || card.classList.contains(selected)) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        });
    });

    const trailerModal = document.getElementById('trailerModal');
    const trailerFrame = document.getElementById('trailerFrame');
    const trailerTitle = document.getElementById('trailerTitle');
    const trailerClose = document.getElementById('trailerClose');
    const trailerButtons = document.querySelectorAll('.trailer-btn');

    function closeTrailer() {
        trailerModal.classList.remove('show');
        trailerModal.setAttribute('aria-hidden', 'true');
        trailerFrame.src = '';
    }

    trailerButtons.forEach(button => {
        button.addEventListener('click', () => {
            trailerTitle.textContent = `${button.dataset.title} Trailer`;
            trailerFrame.src = button.dataset.trailer;
            trailerModal.classList.add('show');
            trailerModal.setAttribute('aria-hidden', 'false');
            trailerClose.focus();
        });
    });

    trailerClose.addEventListener('click', closeTrailer);
    trailerModal.addEventListener('click', (event) => {
        if (event.target === trailerModal) {
            closeTrailer();
        }
    });
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && trailerModal.classList.contains('show')) {
            closeTrailer();
        }
    });
</script>

</body>
</html>
