<?php



?><?php
include 'condb.php';

if (isset($_POST['game_id'])) {
    $game_id = $_POST['game_id'];
    $quantity = $_POST['quantity'];

    // Add to cart (you can enhance with session/user_id)
    $sql = "INSERT INTO cart (game_id, quantity) VALUES ('$game_id', '$quantity')";
    mysqli_query($conn, $sql);

    // Redirect to cart page
    header("Location: cart.php");
    exit();
}
?>
