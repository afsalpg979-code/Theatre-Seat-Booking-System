<?php 
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Remove all booked seats from localStorage (Note: PHP doesn't have localStorage, so we use a file or session to mimic it)
        setcookie('bookedSeats', '', time() - 3600, '/'); // Simulate clearing of booked seats
        header("Location: show-booked-seats.php");  // Redirect back to the booked seats page
        exit();
    }
?>

<?php include('header.php'); ?>

<div class="container">
    <h1>Clear All Booked Seats - FALCONS Theater</h1>
    <p>Are you sure you want to clear all the booked seats? This action cannot be undone.</p>
    
    <form method="POST">
        <button type="submit" style="padding: 12px 20px; background-color: #ff6347; color: white;">Clear All Booked Seats</button>
    </form>
</div>

<?php include('footer.php'); ?>
