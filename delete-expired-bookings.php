<?php
// Include database connection
include 'db.php';

// Get the current time
$currentTime = date('Y-m-d H:i:s');

// Query to get all bookings that have expired (booking_expiry is before the current time)
$query = "SELECT * FROM bookings WHERE booking_expiry < ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $currentTime);
$stmt->execute();
$result = $stmt->get_result();

// If there are any expired bookings
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        // Decode the seats JSON
        $seats = json_decode($row['seats']);
        
        // Remove the expired booking (delete from the database)
        $deleteQuery = "DELETE FROM bookings WHERE id = ?";
        $deleteStmt = $conn->prepare($deleteQuery);
        $deleteStmt->bind_param("i", $row['id']);
        $deleteStmt->execute();
        
        // Log or print the seats that are now freed (optional)
        echo "Booking for seats " . implode(', ', $seats) . " has been deleted. Seats are now free.<br>";
    }
} else {
    echo "No expired bookings found.";
}

// Close the statement and connection
$stmt->close();
$conn->close();
?>
