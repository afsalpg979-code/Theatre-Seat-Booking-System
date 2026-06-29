<?php
// save-booking.php

// Include your database connection file
include 'db.php'; // Make sure your db connection is correct

// Get the raw POST data
$data = json_decode(file_get_contents("php://input"));

// Check if the data is valid
if (!isset($data->name) || !isset($data->phone) || !isset($data->seats) || !isset($data->movie) || !isset($data->totalAmount) || !isset($data->time)) {
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
    exit;
}

// Extract data from the POST request
$name = $data->name;
$phone = $data->phone;
$seats = implode(',', $data->seats); // Convert selected seats array to CSV string
$movie = $data->movie;
$theaters = [
    'T1' => 'T1 - 4K',
    'T2' => 'T2 - 2K',
    'T3' => 'T3 - 3D',
    'T4' => 'T4 - 4DX',
];
$theaterCode = $data->theater ?? 'T1';
$theater = $theaters[$theaterCode] ?? 'T1 - 4K';
$time = $data->time;
$totalAmount = $data->totalAmount;

// Insert booking data into the database
$query = "INSERT INTO bookings (name, phone, seats, movie, theater, totalAmount, time) 
          VALUES ('$name', '$phone', '$seats', '$movie', '$theater', '$totalAmount', '$time')";

if (mysqli_query($conn, $query)) {
    // If insertion is successful, return the booking ID
    $booking_id = mysqli_insert_id($conn);
    echo json_encode(['success' => true, 'booking_id' => $booking_id]);
} else {
    // If there was an error, return an error message
    echo json_encode(['success' => false, 'message' => 'Error saving the booking']);
}

mysqli_close($conn);
?>
