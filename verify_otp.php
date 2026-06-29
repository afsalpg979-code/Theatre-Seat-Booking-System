<?php
session_start();
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $entered_otp = $_POST['otp'];
    if ($entered_otp == $_SESSION['otp']) {
        // OTP is valid: Save user to DB
        $conn = new mysqli("localhost", "root", "", "cinema_db");
        if ($conn->connect_error) die("Connection failed");

        $data = $_SESSION['register_data'];

        $stmt = $conn->prepare("INSERT INTO clients (username, email, password, age, married_status, phone, address, place, pincode, gender) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param(
            "ssssssssss",
            $data['username'],
            $data['email'],
            password_hash($data['password'], PASSWORD_DEFAULT),
            $data['age'],
            $data['married_status'],
            $data['phone'],
            $data['address'],
            $data['place'],
            $data['pincode'],
            $data['gender']
        );
        $stmt->execute();
        $stmt->close();
        $conn->close();

        echo "<h3>Registration successful!</h3>";
        session_destroy();
    } else {
        echo "<h3>Invalid OTP. Please try again.</h3>";
    }
}
?>

<form method="POST">
  <h2>Verify OTP</h2>
  <label>Enter OTP sent to your WhatsApp:</label><br>
  <input type="text" name="otp" required><br><br>
  <button type="submit">Verify & Register</button>
</form>
