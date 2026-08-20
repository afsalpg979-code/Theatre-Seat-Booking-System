<?php
/**
 * FALCONS THEATER
 * add_to_cart.php
 *
 * Adds a movie ticket booking to the user's session cart.
 */

session_start();
require_once 'db.php';

/* -------------------------------------------------------
   Security: Only allow POST requests
------------------------------------------------------- */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit();
}

/* -------------------------------------------------------
   CSRF Protection
------------------------------------------------------- */
if (
    !isset($_POST['csrf_token']) ||
    !isset($_SESSION['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
) {
    $_SESSION['cart_error'] = "Invalid security token. Please try again.";
    header("Location: index.php");
    exit();
}

/* -------------------------------------------------------
   Get and sanitize input
------------------------------------------------------- */
$movie_id    = filter_input(INPUT_POST, 'movie_id', FILTER_VALIDATE_INT);
$movie       = trim($_POST['movie'] ?? '');
$show_date   = trim($_POST['show_date'] ?? '');
$show_time   = trim($_POST['show_time'] ?? '');
$seats       = trim($_POST['seats'] ?? '');
$name        = trim($_POST['name'] ?? '');
$phone       = trim($_POST['phone'] ?? '');
$quantity    = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT);
$totalAmount = filter_input(INPUT_POST, 'totalAmount', FILTER_VALIDATE_FLOAT);

/* -------------------------------------------------------
   Default quantity
------------------------------------------------------- */
if (!$quantity || $quantity < 1) {
    $quantity = 1;
}

/* Maximum 50 seats per booking */
if ($quantity > 50) {
    $_SESSION['cart_error'] = "Maximum 50 seats can be selected.";
    header("Location: booking-confirmation.php");
    exit();
}

/* -------------------------------------------------------
   Validate required fields
------------------------------------------------------- */
if (!$movie_id || $movie === '' || $show_date === '' || $show_time === '') {
    $_SESSION['cart_error'] = "Movie and show details are required.";
    header("Location: booking-confirmation.php");
    exit();
}

if ($seats === '') {
    $_SESSION['cart_error'] = "Please select at least one seat.";
    header("Location: booking-confirmation.php");
    exit();
}

if ($name === '') {
    $_SESSION['cart_error'] = "Please enter your name.";
    header("Location: booking-confirmation.php");
    exit();
}

/* -------------------------------------------------------
   Validate phone
------------------------------------------------------- */
$cleanPhone = preg_replace('/[^0-9+]/', '', $phone);

if (!preg_match('/^\+?[0-9]{10,15}$/', $cleanPhone)) {
    $_SESSION['cart_error'] = "Please enter a valid phone number.";
    header("Location: booking-confirmation.php");
    exit();
}

/* -------------------------------------------------------
   Validate seats
------------------------------------------------------- */
$seatArray = array_filter(
    array_map('trim', explode(',', $seats))
);

$seatArray = array_values(array_unique($seatArray));

if (count($seatArray) !== $quantity) {
    $_SESSION['cart_error'] = "Selected seat quantity does not match.";
    header("Location: booking-confirmation.php");
    exit();
}

/* -------------------------------------------------------
   Validate seat format
   Examples: A1, A2, B5, C10
------------------------------------------------------- */
foreach ($seatArray as $seat) {

    if (!preg_match('/^[A-Z]{1,2}[0-9]{1,2}$/i', $seat)) {
        $_SESSION['cart_error'] = "Invalid seat selection.";
        header("Location: booking-confirmation.php");
        exit();
    }
}

/* -------------------------------------------------------
   Check movie exists
------------------------------------------------------- */
$stmt = $conn->prepare("
    SELECT id, title, language, image, rating, description
    FROM upcoming_movies
    WHERE id = ?
    LIMIT 1
");

$stmt->bind_param("i", $movie_id);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $stmt->close();

    $_SESSION['cart_error'] = "Movie not found.";
    header("Location: index.php");
    exit();
}

$movieData = $result->fetch_assoc();
$stmt->close();

/* -------------------------------------------------------
   Use database movie title instead of trusting POST
------------------------------------------------------- */
$movie = $movieData['title'];

/* -------------------------------------------------------
   Validate amount
------------------------------------------------------- */
if ($totalAmount === false || $totalAmount <= 0) {
    $_SESSION['cart_error'] = "Invalid booking amount.";
    header("Location: booking-confirmation.php");
    exit();
}

/* -------------------------------------------------------
   Create session cart
------------------------------------------------------- */
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

/* -------------------------------------------------------
   Generate unique cart item ID
------------------------------------------------------- */
$cartItemId = uniqid('FT_', true);

/* -------------------------------------------------------
   Store booking information
------------------------------------------------------- */
$_SESSION['cart'][$cartItemId] = [
    'cart_item_id' => $cartItemId,
    'movie_id'     => $movie_id,
    'movie'        => $movie,
    'language'     => $movieData['language'],
    'image'        => $movieData['image'],
    'show_date'    => $show_date,
    'show_time'    => $show_time,
    'seats'        => $seatArray,
    'seat_count'   => count($seatArray),
    'name'         => htmlspecialchars($name, ENT_QUOTES, 'UTF-8'),
    'phone'        => $cleanPhone,
    'totalAmount'  => number_format((float)$totalAmount, 2, '.', ''),
    'added_at'     => date('Y-m-d H:i:s')
];

/* -------------------------------------------------------
   Cart count
------------------------------------------------------- */
$_SESSION['cart_count'] = count($_SESSION['cart']);

/* -------------------------------------------------------
   Success message
------------------------------------------------------- */
$_SESSION['cart_success'] = "Movie tickets added to your booking cart.";

/* -------------------------------------------------------
   Redirect to cart
------------------------------------------------------- */
header("Location: cart.php");
exit();
?>
