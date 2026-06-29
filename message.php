<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "cinema_db";

// Check if form submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = htmlspecialchars($_POST["name"]);
    $email = htmlspecialchars($_POST["email"]);
    $message = htmlspecialchars($_POST["message"]);

    // Connect to database
    $conn = new mysqli($servername, $username, $password, $dbname);

    if ($conn->connect_error) {
        die("<h2 style='color:red;'>❌ Connection failed: " . $conn->connect_error . "</h2>");
    }

    // Insert message
    $stmt = $conn->prepare("INSERT INTO messages (name, email, message) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $name, $email, $message);

    if ($stmt->execute()) {
        $response = "<h2 style='color:green;'>✅ Thank you, <strong>$name</strong>! Your message has been sent.</h2>";
    } else {
        $response = "<h2 style='color:red;'>❌ Failed to send your message. Please try again later.</h2>";
    }

    $stmt->close();
    $conn->close();
} else {
    $response = "<h2 style='color:red;'>Invalid request.</h2>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Message Status - FALCONS MOVIES</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(to right, #0f2027, #203a43, #2c5364);
            color: #fff;
            text-align: center;
            padding: 50px;
        }

        .box {
            background: #fff;
            color: #333;
            padding: 40px;
            border-radius: 12px;
            max-width: 500px;
            margin: 0 auto;
            box-shadow: 0 0 20px rgba(0,0,0,0.3);
        }

        a {
            display: inline-block;
            margin-top: 20px;
            text-decoration: none;
            color: #00cc99;
            font-weight: bold;
        }

        a:hover {
            color: #009973;
        }
    </style>
</head>
<body>

<div class="box">
    <?php echo $response; ?>
    <a href="index.php">⬅️ Back to Contact Page</a>
</div>

</body>
</html>
