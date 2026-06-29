const THREE_URL = 'https://unpkg.com/three@0.160.0/build/three.module.js';

const config = window.theaterBookingConfig || {};
const movieName = config.movieName || 'FALCONS Theater';
const selectedTheater = config.selectedTheater || 'T1';
const seatPrice = Number(config.seatPrice || 100);
const seatCount = Number(config.seatCount || 100);
const seatSections = Array.isArray(config.seatSections) && config.seatSections.length
    ? config.seatSections.map(section => ({
        ...section,
        start: Number(section.start),
        end: Number(section.end),
        price: Number(section.price || seatPrice)
    }))
    : [{ name: 'Standard', start: 1, end: seatCount, price: seatPrice, color: '#22c55e' }];

const seatsContainer = document.getElementById('seats-container');
const loadingBox = document.getElementById('seat-loading');
const theaterSelect = document.getElementById('theater-select');
const showTimeSelect = document.getElementById('show-time');
const totalAmountElem = document.getElementById('total-amount');
const selectedSeatsElem = document.getElementById('selected-seats');
const userNameInput = document.getElementById('user-name');
const userPhoneInput = document.getElementById('user-phone');
const clearSelectionBtn = document.getElementById('clear-selection');
const confirmSelectionBtn = document.getElementById('confirm-selection');
const showAllBookedBtn = document.getElementById('show-all-booked');

let selectedSeats = [];
let bookedSeatIds = new Set();
let renderScene = null;

function getSeatSection(seatId) {
    return seatSections.find(section => seatId >= section.start && seatId <= section.end) || seatSections[0];
}

function getSeatPrice(seatId) {
    return getSeatSection(seatId).price;
}

function formatAmount(amount) {
    return Number.isInteger(amount) ? String(amount) : amount.toFixed(2);
}

function setLoading(message, visible = true) {
    if (!loadingBox) {
        return;
    }

    loadingBox.textContent = message;
    loadingBox.style.display = visible ? 'flex' : 'none';
}

function updateTotalAmount() {
    const totalAmount = selectedSeats.reduce((sum, seatId) => sum + getSeatPrice(seatId), 0);
    totalAmountElem.textContent = `Total Amount: Rs.${formatAmount(totalAmount)}`;
    selectedSeatsElem.textContent = selectedSeats.length
        ? `Selected seats: ${selectedSeats.slice().sort((a, b) => a - b).map(seatId => {
            const section = getSeatSection(seatId);
            return `${seatId} (${section.name})`;
        }).join(', ')}`
        : 'Selected seats: none';
}

async function loadBookedSeats() {
    selectedSeats = [];
    updateTotalAmount();
    setLoading('Loading booked seats...');

    const params = new URLSearchParams({
        movie: movieName,
        theater: theaterSelect.value,
        time: showTimeSelect.value
    });

    try {
        const response = await fetch(`fetch-booked-seats.php?${params.toString()}`);
        const data = await response.json();
        const seats = Array.isArray(data.bookedSeats) ? data.bookedSeats : [];
        bookedSeatIds = new Set(seats.map(seat => Number(seat.seatId)));
    } catch (error) {
        console.error('Error fetching booked seats:', error);
        bookedSeatIds = new Set();
    }

    if (renderScene) {
        renderScene();
        setLoading('', false);
    } else {
        createFallbackSeats();
    }
}

function toggleSeat(seatId) {
    if (bookedSeatIds.has(seatId)) {
        alert('This seat is already booked!');
        return;
    }

    if (selectedSeats.includes(seatId)) {
        selectedSeats = selectedSeats.filter(id => id !== seatId);
    } else {
        selectedSeats.push(seatId);
    }

    updateTotalAmount();

    if (renderScene) {
        renderScene();
    }
}

function createFallbackSeats() {
    seatsContainer.innerHTML = '';
    seatsContainer.style.display = 'grid';
    seatsContainer.style.gridTemplateColumns = 'repeat(10, minmax(32px, 1fr))';
    seatsContainer.style.gap = '9px';
    seatsContainer.style.padding = '18px';
    seatsContainer.style.height = 'auto';
    seatsContainer.style.minHeight = '0';

    seatSections.forEach(section => {
        const heading = document.createElement('div');
        heading.textContent = `${section.name} - Rs.${formatAmount(section.price)}`;
        heading.style.gridColumn = '1 / -1';
        heading.style.margin = section.start === 1 ? '0 0 2px' : '12px 0 2px';
        heading.style.color = section.color || '#d8e6f2';
        heading.style.fontWeight = '800';
        heading.style.textAlign = 'center';
        seatsContainer.appendChild(heading);

        for (let seatId = section.start; seatId <= section.end; seatId++) {
            const button = document.createElement('button');
            button.type = 'button';
            button.textContent = seatId;
            button.style.minHeight = '38px';
            button.style.border = '0';
            button.style.borderRadius = '7px';
            button.style.fontWeight = '700';
            button.style.cursor = 'pointer';

            if (bookedSeatIds.has(seatId)) {
                button.style.background = '#8b949e';
                button.style.color = '#101820';
                button.style.cursor = 'not-allowed';
            } else if (selectedSeats.includes(seatId)) {
                button.style.background = '#f59e0b';
                button.style.color = '#111';
            } else {
                button.style.background = section.color || '#22c55e';
                button.style.color = '#07131d';
            }

            button.addEventListener('click', () => toggleSeat(seatId));
            seatsContainer.appendChild(button);
        }
    });
}

function initThreeTheater(THREE) {
    const scene = new THREE.Scene();
    scene.background = new THREE.Color(0x07131d);

    const camera = new THREE.PerspectiveCamera(42, 1, 0.1, 100);
    camera.position.set(0, 12.5, 17);
    camera.lookAt(0, 0, -1.5);

    const renderer = new THREE.WebGLRenderer({ antialias: true, alpha: false });
    renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, 2));
    renderer.shadowMap.enabled = true;
    seatsContainer.appendChild(renderer.domElement);

    const raycaster = new THREE.Raycaster();
    const pointer = new THREE.Vector2();
    const seatGroups = new Map();

    const materials = {
        available: new Map(),
        selected: new THREE.MeshStandardMaterial({ color: 0xf59e0b, roughness: 0.5, metalness: 0.18 }),
        booked: new THREE.MeshStandardMaterial({ color: 0x8b949e, roughness: 0.7, metalness: 0.05 }),
        floor: new THREE.MeshStandardMaterial({ color: 0x101820, roughness: 0.86, metalness: 0.08 }),
        screen: new THREE.MeshStandardMaterial({ color: 0xdbeafe, roughness: 0.35, metalness: 0.15, emissive: 0x1d4ed8, emissiveIntensity: 0.18 }),
        trim: new THREE.MeshStandardMaterial({ color: 0xf3b61f, roughness: 0.42, metalness: 0.22 })
    };

    function getSectionMaterial(seatId) {
        const section = getSeatSection(seatId);
        if (!materials.available.has(section.name)) {
            materials.available.set(section.name, new THREE.MeshStandardMaterial({
                color: new THREE.Color(section.color || '#22c55e'),
                roughness: 0.55,
                metalness: section.name.toLowerCase() === 'gold' ? 0.28 : 0.12
            }));
        }

        return materials.available.get(section.name);
    }

    scene.add(new THREE.HemisphereLight(0xfff1c4, 0x0f172a, 1.6));

    const keyLight = new THREE.DirectionalLight(0xffffff, 1.2);
    keyLight.position.set(5, 10, 8);
    keyLight.castShadow = true;
    scene.add(keyLight);

    const stageLight = new THREE.PointLight(0xf3b61f, 1.7, 24);
    stageLight.position.set(0, 5.5, -8);
    scene.add(stageLight);

    const floor = new THREE.Mesh(new THREE.BoxGeometry(17, 0.25, 20), materials.floor);
    floor.position.set(0, -0.28, 0);
    floor.receiveShadow = true;
    scene.add(floor);

    const screen = new THREE.Mesh(new THREE.BoxGeometry(12, 3.5, 0.2), materials.screen);
    screen.position.set(0, 3.1, -10);
    screen.castShadow = true;
    scene.add(screen);

    const screenTrim = new THREE.Mesh(new THREE.BoxGeometry(13, 0.16, 0.28), materials.trim);
    screenTrim.position.set(0, 5, -9.9);
    scene.add(screenTrim);

    function applySeatMaterial(group, seatId) {
        let material = getSectionMaterial(seatId);
        if (bookedSeatIds.has(seatId)) {
            material = materials.booked;
        } else if (selectedSeats.includes(seatId)) {
            material = materials.selected;
        }

        group.traverse(child => {
            if (child.isMesh && child.userData.part === 'seat') {
                child.material = material;
            }
        });
    }

    function makeSeatNumberTexture(seatId) {
        const canvas = document.createElement('canvas');
        canvas.width = 128;
        canvas.height = 128;
        const context = canvas.getContext('2d');
        context.fillStyle = '#ffffff';
        context.font = '700 58px Arial';
        context.textAlign = 'center';
        context.textBaseline = 'middle';
        context.fillText(String(seatId), 64, 68);
        const texture = new THREE.CanvasTexture(canvas);
        texture.colorSpace = THREE.SRGBColorSpace;
        return texture;
    }

    function createSeat(seatId, x, z, rowLift) {
        const group = new THREE.Group();
        group.position.set(x, rowLift, z);
        group.userData.seatId = seatId;

        const seatBase = new THREE.Mesh(new THREE.BoxGeometry(0.72, 0.22, 0.72), getSectionMaterial(seatId));
        seatBase.position.set(0, 0.18, 0);
        seatBase.castShadow = true;
        seatBase.userData = { seatId, part: 'seat' };
        group.add(seatBase);

        const seatBack = new THREE.Mesh(new THREE.BoxGeometry(0.72, 0.78, 0.18), getSectionMaterial(seatId));
        seatBack.position.set(0, 0.55, 0.34);
        seatBack.rotation.x = -0.16;
        seatBack.castShadow = true;
        seatBack.userData = { seatId, part: 'seat' };
        group.add(seatBack);

        const numberMaterial = new THREE.SpriteMaterial({
            map: makeSeatNumberTexture(seatId),
            transparent: true,
            depthTest: false
        });
        const numberSprite = new THREE.Sprite(numberMaterial);
        numberSprite.scale.set(0.35, 0.35, 0.35);
        numberSprite.position.set(0, 0.62, -0.38);
        group.add(numberSprite);

        scene.add(group);
        seatGroups.set(seatId, group);
    }

    const rows = Math.ceil(seatCount / 10);
    const cols = 10;
    const xGap = 1.12;
    const zGap = 0.88;
    const startX = -((cols - 1) * xGap) / 2;
    const startZ = 5.5;

    for (let row = 0; row < rows; row++) {
        for (let col = 0; col < cols; col++) {
            const seatId = row * cols + col + 1;
            if (seatId > seatCount) {
                continue;
            }
            createSeat(seatId, startX + col * xGap, startZ - row * zGap, row * 0.11);
        }
    }

    renderScene = function renderTheater() {
        seatGroups.forEach((group, seatId) => applySeatMaterial(group, seatId));
        renderer.render(scene, camera);
    };

    function resizeTheater() {
        const width = seatsContainer.clientWidth || 900;
        const height = seatsContainer.clientHeight || 520;
        camera.aspect = width / height;
        camera.updateProjectionMatrix();
        renderer.setSize(width, height, false);
        renderScene();
    }

    function seatFromPointer(event) {
        const rect = renderer.domElement.getBoundingClientRect();
        pointer.x = ((event.clientX - rect.left) / rect.width) * 2 - 1;
        pointer.y = -((event.clientY - rect.top) / rect.height) * 2 + 1;
        raycaster.setFromCamera(pointer, camera);

        const hits = raycaster.intersectObjects(scene.children, true);
        for (const hit of hits) {
            let object = hit.object;
            while (object) {
                if (object.userData && object.userData.seatId) {
                    return Number(object.userData.seatId);
                }
                object = object.parent;
            }
        }

        return null;
    }

    renderer.domElement.addEventListener('pointerdown', event => {
        const seatId = seatFromPointer(event);
        if (seatId) {
            toggleSeat(seatId);
        }
    });

    window.addEventListener('resize', resizeTheater);
    resizeTheater();
    setLoading('', false);
}

function clearSelection() {
    selectedSeats = [];
    updateTotalAmount();

    if (renderScene) {
        renderScene();
    } else {
        createFallbackSeats();
    }
}

async function submitBooking() {
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
        movie: movieName,
        theater: theaterSelect.value,
        time: showTimeSelect.value,
        totalAmount: selectedSeats.reduce((sum, seatId) => sum + getSeatPrice(seatId), 0)
    };

    try {
        const response = await fetch(window.location.pathname + window.location.search, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(bookingDetails)
        });
        const data = await response.json();

        if (data.success) {
            window.location.href = `${window.location.pathname}?movie=${encodeURIComponent(movieName)}&theater=${encodeURIComponent(theaterSelect.value)}&time=${encodeURIComponent(showTimeSelect.value)}&id=${data.booking_id}`;
            return;
        }

        alert(data.error || 'Error saving booking. Please try again.');
        await loadBookedSeats();
    } catch (error) {
        console.error('Error:', error);
        alert('There was an error processing your booking.');
    }
}

async function boot() {
    updateTotalAmount();
    clearSelectionBtn.addEventListener('click', clearSelection);
    confirmSelectionBtn.addEventListener('click', submitBooking);
    showAllBookedBtn.addEventListener('click', () => {
        window.location.href = 'show-booked-seats.php';
    });
    theaterSelect.value = selectedTheater;
    theaterSelect.addEventListener('change', loadBookedSeats);
    showTimeSelect.addEventListener('change', loadBookedSeats);

    try {
        const THREE = await import(THREE_URL);
        initThreeTheater(THREE);
    } catch (error) {
        console.error('Three.js could not be loaded:', error);
        setLoading('3D view is unavailable. Showing standard seats instead.', false);
        renderScene = null;
        createFallbackSeats();
    }

    await loadBookedSeats();
}

boot();
