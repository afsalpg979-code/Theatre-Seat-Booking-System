<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "cinema_db";

// Connect to DB
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$message = "";

// Handle sign up
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = ucfirst(strtolower(trim($_POST["username"])));  // First letter capital
    $email = strtolower(trim($_POST["email"]));                 // All lowercase
    $plain_password = trim($_POST["password"]);
    $age = intval($_POST["age"]);
    $married_status = $_POST["married_status"];
    $phone = trim($_POST["phone"]);
    $address = trim($_POST["address"]);
    $place = trim($_POST["place"]);
    $pincode = trim($_POST["pincode"]);
    $gender = $_POST["gender"];

    // Validate
    if ($age < 18) {
        $message = "❌ You must be at least 18 years old to register.";
    } elseif (!preg_match('/^\d{10}$/', $phone)) {
        $message = "❌ Phone number must be exactly 10 digits.";
    } else {
        $hashed_password = password_hash($plain_password, PASSWORD_DEFAULT);

        $stmt = $conn->prepare(
            "INSERT INTO clients 
            (username, email, password, age, married_status, phone, address, place, pincode, gender)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param(
            "sssissssss",
            $username, $email, $hashed_password, $age, $married_status,
            $phone, $address, $place, $pincode, $gender
        );

        if ($stmt->execute()) {
            $message = "✅ Registration successful! You can now log in.";
        } else {
            $message = "❌ Error: " . $stmt->error;
        }

        $stmt->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Client Sign Up - FALCONS THEATER</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(to right, #0f2027, #203a43, #2c5364);
            color: #fff;
            padding: 40px;
        }
        h1 {
            text-align: center;
            color: #00ffcc;
        }
        form {
            max-width: 600px;
            margin: 0 auto;
            background: #fff;
            color: #333;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 25px rgba(0,0,0,0.3);
        }
        label {
            display: block;
            margin-top: 15px;
            font-weight: bold;
        }
        input, select, textarea {
            width: 100%;
            padding: 10px;
            margin-top: 5px;
            border-radius: 5px;
            border: 1px solid #ccc;
        }
        button {
            margin-top: 20px;
            padding: 12px 20px;
            background: #00cc99;
            color: #fff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        button:hover {
            background: #009973;
        }
        .message {
            text-align: center;
            margin-top: 20px;
            color: yellow;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <h1>Client Sign Up - FALCONS THEATER</h1>

    <form method="POST" action="">
        <label>Username</label>
        <input type="text" name="username" required>

        <label>Email</label>
        <input type="email" name="email" required>

        <label>Password</label>
        <input type="password" name="password" required>

        <label>Age (18+)</label>
        <input type="number" name="age" min="18" required>

        <label>Marital Status</label>
        <select name="married_status" required>
            <option value="Unmarried">Unmarried</option>
            <option value="Married">Married</option>
        </select>

        <label>Phone Number (10 digits)</label>
        <input type="text" name="phone" required>

        <label>Address</label>
        <textarea name="address" required></textarea>

        <label>Place</label>
        <input type="text" name="place" required>

        <label>Pincode</label>
        <input type="text" name="pincode" required>

        <label>Gender</label>
        <select name="gender" required>
            <option value="Male">Male</option>
            <option value="Female">Female</option>
        </select>

        <button type="submit">Sign Up</button>
    </form>

    <?php if ($message): ?>
        <p class="message"><?= htmlspecialchars($message) ?></p>
    <?php endif; ?>
</body>
</html>
