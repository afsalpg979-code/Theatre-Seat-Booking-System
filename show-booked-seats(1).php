<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seat Booking - FALCONS Theater</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            text-align: center;
            margin: 20px;
        }
        .seats-container {
            display: grid;
            grid-template-columns: repeat(10, 40px); /* 10 seats per row */
            grid-gap: 10px;
            justify-content: center;
        }
        .seat {
            width: 40px;
            height: 40px;
            background-color: lightgreen; /* Free seat color */
            text-align: center;
            line-height: 40px;
            cursor: pointer;
        }
        .selected {
            background-color: lightcoral; /* Selected seat color */
        }
        .booked {
            background-color: lightgray; /* Booked seat color */
            cursor: not-allowed; /* Disable click on booked seat */
        }
        .input-group input {
            padding: 10px;
            margin: 10px;
        }
        .actions button {
            padding: 10px 20px;
            font-size: 16px;
            margin-top: 10px;
        }
        .total-amount {
            font-size: 20px;
            font-weight: bold;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <h1>Seat Booking - FALCONS Theater</h1>

    <div class="seats-container" id="seats-container"></div>

    <div class="total-amount" id="total-amount">Total Amount: ₹0</div>

    <div class="input-group">
        <input type="text" id="user-name" placeholder="Enter your Name" />
        <input type="text" id="user-phone" placeholder="Enter your Phone Number" />
    </div>

    <div class="actions">
        <button id="clear-selection">Clear Selection</button>
        <button id="confirm-selection">Confirm Selection</button>
        <button id="show-all-booked">Show All Booked</button>
    </div>

    <a href="example.html" class="book-btn">Back to Movie Listings</a>

    <script>
        const seatsContainer = document.getElementById('seats-container');
        const totalAmountElem = document.getElementById('total-amount');
        const userNameInput = document.getElementById('user-name');
        const userPhoneInput = document.getElementById('user-phone');
        const clearSelectionBtn = document.getElementById('clear-selection');
        const confirmSelectionBtn = document.getElementById('confirm-selection');
        const showAllBookedBtn = document.getElementById('show-all-booked');
        const seatPrice = 100;
        let selectedSeats = [];
        let bookedSeats = JSON.parse(localStorage.getItem('bookedSeats')) || [];

        const seatCount = 160;  // Total number of seats

        // Create seats dynamically
        for (let i = 0; i < seatCount; i++) {
            const seat = document.createElement('div');
            seat.classList.add('seat');
            seat.dataset.seatId = i + 1;  // Seat ID starting from 1
            seat.textContent = i + 1;
            seatsContainer.appendChild(seat);

            // Check if seat is already booked
            if (bookedSeats.some(seat => seat.seatId === i + 1)) {
                seat.classList.add('booked');
            }

            // Add click event listener to seat
            seat.addEventListener('click', () => toggleSeatSelection(seat));
        }

        function toggleSeatSelection(seat) {
            // Check if seat is booked
            if (seat.classList.contains('booked')) {
                alert('This seat is already booked!');
                return;
            }

            // Toggle seat selection
            if (seat.classList.contains('selected')) {
                seat.classList.remove('selected');
                selectedSeats = selectedSeats.filter(id => id !== parseInt(seat.dataset.seatId));
            } else {
                seat.classList.add('selected');
                selectedSeats.push(parseInt(seat.dataset.seatId));
            }

            updateTotalAmount();
        }

        function updateTotalAmount() {
            const totalAmount = selectedSeats.length * seatPrice;
            totalAmountElem.textContent = `Total Amount: ₹${totalAmount}`;
        }

        clearSelectionBtn.addEventListener('click', () => {
            selectedSeats = [];
            updateSeats();
        });

        confirmSelectionBtn.addEventListener('click', () => {
            if (selectedSeats.length === 0) {
                alert('Please select seats before confirming.');
                return;
            }

            const userName = userNameInput.value.trim();
            const userPhone = userPhoneInput.value.trim();

            if (!userName || !userPhone) {
                alert('Please enter your name and phone number.');
                return;
            }

            const bookingDetails = {
                name: userName,
                phone: userPhone,
                seats: selectedSeats,
                time: new Date().toLocaleString(),
                totalAmount: selectedSeats.length * seatPrice
            };

            selectedSeats.forEach(seatId => {
                bookedSeats.push({ seatId, ...bookingDetails });
            });

            localStorage.setItem('bookedSeats', JSON.stringify(bookedSeats));
            selectedSeats = [];
            updateSeats();
            alert('Seats successfully booked!');
            window.location.href = 'booking-confirmation.html';
        });

        showAllBookedBtn.addEventListener('click', () => {
            window.location.href = 'show-booked-seats.html';
        });

        function updateSeats() {
            const allSeats = seatsContainer.querySelectorAll('.seat');
            allSeats.forEach(seat => {
                const seatId = parseInt(seat.dataset.seatId);
                seat.classList.remove('selected');
                if (bookedSeats.some(booked => booked.seatId === seatId)) {
                    seat.classList.add('booked');
                }
            });
            updateTotalAmount();
        }
    </script>
</body>
</html>
