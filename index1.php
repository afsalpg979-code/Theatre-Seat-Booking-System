<?php
$movies = [
    [
        'title' => 'Marco',
        'language' => 'Malayalam',
        'image' => 'https://upload.wikimedia.org/wikipedia/en/b/b3/Marco_Malayalam_film.jpg',
        'rating' => '8.2/10',
        'votes' => '12.3k',
        'description' => 'A thrilling action-packed adventure set in the heart of Kerala, starring the legendary actor Marco.'
    ],
    [
        'title' => 'Maleficent: Mistress of Evil',
        'language' => 'English',
        'image' => 'https://encrypted-tbn1.gstatic.com/images?q=tbn:ANd9GcRlmyFgOALxL8qHaMVP0V1tnke84urGRx_xc24EC1iI033qMsLR',
        'rating' => '7.0/10',
        'votes' => '45.6k',
        'description' => 'Maleficent and Aurora face new challenges that test the strength of their bond.'
    ],
    [
        'title' => 'WALL-E',
        'language' => 'English',
        'image' => 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcSxYd3qWb7UlM_UBeis23ZV3SHzJpNy9-aBeScMnEzLHO1zc0wD',
        'rating' => '8.4/10',
        'votes' => '150k',
        'description' => 'A small waste-collecting robot embarks on a space journey that ultimately decides the fate of mankind.'
    ],
    [
        'title' => 'Meiyazhagan',
        'language' => 'Tamil',
        'image' => 'https://encrypted-tbn1.gstatic.com/images?q=tbn:ANd9GcRN7c6D9Gu8Q7-Xy4VnUJg26SaACG6SVgev746Cj5lhUCENPWuh',
        'rating' => '7.8/10',
        'votes' => '34.5k',
        'description' => 'A story about love, pain, and a quest for redemption.'
    ],
    [
        'title' => 'Baby John',
        'language' => 'Hindi',
        'image' => 'https://encrypted-tbn3.gstatic.com/images?q=tbn:ANd9GcT4b3Vyq_KIoV8z73rw-BOuny22C3GZ2gvbQHk3HdCXeDKalido',
        'rating' => '8.2/10',
        'votes' => '12.3k',
        'description' => 'A Hindi action thriller remake of the 2016 Tamil film "Theri", starring Varun Dhawan, Keerthy Suresh, and Wamiqa Gabbi.'
    ]
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>FALCONS Theater - Upcoming</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Styling & Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

    <style>
        body { font-family: 'Poppins', sans-serif; margin: 0; background: black; color: #fff; }
        header { background: #111; color: white; text-align: center; padding: 20px; }
        .movies { display: flex; flex-wrap: wrap; justify-content: center; gap: 30px; padding: 20px; }
        .movie-card { width: 250px; background: #222; border-radius: 10px; overflow: hidden; text-align: center; }
        .movie-card img { width: 100%; height: 350px; object-fit: cover; }
        .movie-card h3 { color: #FFD700; margin: 10px 0; }
        .book-btn { display: inline-block; margin: 10px; padding: 10px 20px; background: #FFD700; color: black; text-decoration: none; border-radius: 5px; }
    </style>
</head>
<body>

<header>
    <h1>FALCONS Theater - Upcoming Movies</h1>
</header>

<div class="movies">
    <?php foreach ($movies as $movie): ?>
        <?php
            $bookingPage = 'booking-confirmation.php';
            if ($movie['title'] === 'Maleficent: Mistress of Evil') $bookingPage = 'booking-confirmation1.php';
            elseif ($movie['title'] === 'WALL-E') $bookingPage = 'booking-confirmation2.php';
            elseif ($movie['language'] === 'Tamil') $bookingPage = 'booking-confirmation3.php';
        ?>
        <div class="movie-card">
            <img src="<?= $movie['image'] ?>" alt="<?= $movie['title'] ?>">
            <h3><?= $movie['title'] ?></h3>
            <p><?= $movie['description'] ?></p>
            <a href="<?= $bookingPage ?>?movie=<?= urlencode($movie['title']); ?>" class="book-btn">Book Now</a>
        </div>
    <?php endforeach; ?>
</div>


document.addEventListener('DOMContentLoaded', () => {
    const bookBtn = document.getElementById('bookBtn');
    const nameInput = document.getElementById('name');
    const phoneInput = document.getElementById('phone');
    const seatCheckboxes = document.querySelectorAll('input[name="seats[]"]');

    const movieName = new URLSearchParams(window.location.search).get('movie') || 'FALCONS Theater';

    bookBtn.addEventListener('click', () => {
        const name = nameInput.value.trim();
        const phone = phoneInput.value.trim();
        const seats = Array.from(seatCheckboxes)
            .filter(cb => cb.checked)
            .map(cb => cb.value);

        const totalAmount = seats.length * 100; // Example: ₹100 per seat

        if (!name || !phone || seats.length === 0) {
            alert('Please enter name, phone and select at least one seat.');
            return;
        }

        const bookingDetails = {
            name: name,
            phone: phone,
            seats: seats,
            totalAmount: totalAmount,
            movie: movieName
        };

        fetch('booking-confirmation.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(bookingDetails)
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                alert('Booking successful!');
                window.location.href = 'success.html'; // or your desired redirect
            } else {
                alert('Booking failed: ' + data.message);
            }
        })
        .catch(err => {
            console.error(err);
            alert('Error while booking.');
        });
    });
});
</script>
</body>
</html>
<script>