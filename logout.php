<?php
session_start();
session_destroy();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kopi Janti - Logout</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f5e6d3 0%, #e8d4c0 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .logout-container {
            background: white;
            border-radius: 30px;
            padding: 60px 50px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            max-width: 500px;
            width: 100%;
        }

        .logo {
            margin-bottom: 30px;
        }

        .logo img {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            border: 5px solid #8d6e63;
        }

        h1 {
            font-size: 32px;
            color: #3e2723;
            margin-bottom: 15px;
        }

        .message {
            font-size: 16px;
            color: #666;
            margin-bottom: 40px;
            line-height: 1.6;
        }

        .btn-group {
            display: flex;
            gap: 15px;
            justify-content: center;
        }

        .btn {
            padding: 15px 35px;
            border: none;
            border-radius: 25px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }

        .btn-primary {
            background: #8d6e63;
            color: white;
        }

        .btn-primary:hover {
            background: #6d4c41;
            transform: scale(1.05);
        }

        .btn-secondary {
            background: #e0e0e0;
            color: #5d4037;
        }

        .btn-secondary:hover {
            background: #d0d0d0;
            transform: scale(1.05);
        }

        .icon {
            font-size: 60px;
            margin-bottom: 20px;
        }

        @media (max-width: 768px) {
            .logout-container {
                padding: 40px 30px;
            }

            h1 {
                font-size: 24px;
            }

            .btn-group {
                flex-direction: column;
            }

            .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="logout-container">
        <div class="logo">
            <img src="assets/logo.png" alt="Kopi Janti">
        </div>
        
        <h1>Sampai Jumpa!</h1>
        
        <p class="message">
            Anda telah berhasil logout dari sistem Kopi Janti Kitchen.<br>
            Terima kasih atas kerja keras Anda hari ini!
        </p>
        
        <div class="btn-group">
            <a href="dashboard_kitchen.php" class="btn btn-primary">Kembali ke Dashboard</a>
            <a href="login.php" class="btn btn-secondary">Login Kembali</a>
        </div>
    </div>
</body>
</html>
