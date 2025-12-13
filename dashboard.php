<?php include 'config.php'; ?>
<!DOCTYPE html>
<html>
<head>
    <title>Dashboard</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<div class="sidebar">
    <a href="dashboard.php" class="nav-item active">
        <div class="nav-icon"><img src="assets/icons/home.svg"></div>
        <div class="nav-label">Home</div>
    </a>
    <a href="menu.php" class="nav-item">
        <div class="nav-icon"><img src="assets/icons/menu.svg"></div>
        <div class="nav-label">Menu</div>
    </a>
    <a href="history.php" class="nav-item">
        <div class="nav-icon"><img src="assets/icons/history.svg"></div>
        <div class="nav-label">History</div>
    </a>
    <a href="logout.php" class="nav-item logout">
        <div class="nav-icon"><img src="assets/icons/logout.svg"></div>
        <div class="nav-label">Logout</div>
    </a>
</div>

<div class="main">
    <h1>Order List</h1>

    <div class="card">
        <strong>Nabila Marwa</strong><br>
        No Meja: 7<br><br>

        1x Kopi Cinta — Rp15.000<br>
        1x Telur Cina — Rp15.000<br>
        2x Longblack — Rp30.000<br><br>

        <button>🍳 Dimasak</button>
        <button>🔔 Siap</button>
        <button>✓ Selesai</button>
    </div>
</div>

</body>
</html>
