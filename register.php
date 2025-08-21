<?php
require_once 'auth.php';
require_once 'db.php';

$auth = new Auth($conn);
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = $auth->register($_POST['username'], $_POST['email'], $_POST['password']);
    if ($result['success']) {
        header('Location: login_fixed.php?registered=1');
        exit();
    } else {
        $message = $result['message'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Manga Library</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="home.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(rgba(0, 0, 0, 0.4), rgba(0, 0, 0, 0.4)), 
                        url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1920 1080"><defs><linearGradient id="sky" x1="0%" y1="0%" x2="0%" y2="100%"><stop offset="0%" style="stop-color:%2387CEEB"/><stop offset="100%" style="stop-color:%23228B22"/></linearGradient></defs><rect width="100%" height="100%" fill="url(%23sky)"/><path d="M0,800 Q480,700 960,800 T1920,800 L1920,1080 L0,1080 Z" fill="%23006400"/><path d="M0,850 Q320,750 640,850 T1280,850 L1280,1080 L0,1080 Z" fill="%23228B22"/><path d="M640,900 Q960,800 1280,900 T1920,900 L1920,1080 L640,1080 Z" fill="%23006400"/></svg>');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            overflow: hidden;
            position: relative;
        }

        /* Animated falling leaves */
        .leaf {
            position: absolute;
            width: 20px;
            height: 20px;
            background: linear-gradient(45deg, #ff6b35, #f7931e, #ffcc02);
            border-radius: 0 100% 0 100%;
            opacity: 0.8;
            animation: fall linear infinite;
            z-index: 1;
        }

        .leaf:nth-child(odd) {
            background: linear-gradient(45deg, #e63946, #f77f00, #fcbf49);
            border-radius: 100% 0 100% 0;
        }

        @keyframes fall {
            0% {
                transform: translateY(-100vh) rotate(0deg);
                opacity: 1;
            }
            100% {
                transform: translateY(100vh) rotate(360deg);
                opacity: 0;
            }
        }

        .auth-container {
            position: relative;
            width: 100%;
            max-width: 420px;
            padding: 60px 40px;
            background: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 25px 45px rgba(0, 0, 0, 0.4);
            overflow: hidden;
            z-index: 10;
        }

        .auth-container::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(45deg, transparent, rgba(255, 255, 255, 0.1), transparent);
            transform: rotate(45deg);
            animation: shine 3s linear infinite;
        }

        @keyframes shine {
            0% {
                transform: translateX(-100%) translateY(-100%) rotate(45deg);
            }
            100% {
                transform: translateX(100%) translateY(100%) rotate(45deg);
            }
        }

        .auth-container h2 {
            text-align: center;
            margin-bottom: 40px;
            color: #fff;
            font-size: 2.2em;
            font-weight: 300;
            letter-spacing: 1px;
            position: relative;
            z-index: 1;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
        }

        .auth-form {
            position: relative;
            z-index: 1;
        }

        .form-group {
            margin-bottom: 25px;
            position: relative;
        }

        .auth-form input {
            width: 100%;
            padding: 15px 20px;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 50px;
            color: #fff;
            font-size: 16px;
            transition: all 0.3s ease;
            outline: none;
        }

        .auth-form input:focus {
            background: rgba(255, 255, 255, 0.2);
            border-color: rgba(255, 255, 255, 0.5);
            box-shadow: 0 0 15px rgba(255, 255, 255, 0.2);
        }

        .auth-form input::placeholder {
            color: rgba(255, 255, 255, 0.7);
        }

        .auth-form button {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #2d5016, #4a7c59);
            color: white;
            border: none;
            border-radius: 50px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        .auth-form button:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
            background: linear-gradient(135deg, #4a7c59, #2d5016);
        }

        .auth-form button:active {
            transform: translateY(0);
        }

        .auth-links {
            text-align: center;
            margin-top: 30px;
            position: relative;
            z-index: 1;
        }

        .auth-links a {
            color: rgba(255, 255, 255, 0.9);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .auth-links a:hover {
            color: #fff;
            text-decoration: underline;
        }

        .error-message {
            text-align: center;
            margin-bottom: 20px;
            padding: 15px;
            border-radius: 50px;
            font-weight: 500;
            position: relative;
            z-index: 1;
            color: #ff6b6b;
            background: rgba(255, 107, 107, 0.2);
            border: 1px solid rgba(255, 107, 107, 0.3);
        }

        nav {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            padding: 20px;
            background: rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            color: #fff;
            font-size: 1.5em;
            font-weight: 600;
        }

        nav ul {
            display: flex;
            list-style: none;
            gap: 30px;
        }

        nav a {
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            transition: color 0.3s ease;
        }

        nav a:hover {
            color: #fff;
        }

        @media (max-width: 480px) {
            .auth-container {
                margin: 20px;
                padding: 40px 30px;
            }
            
            .auth-container h2 {
                font-size: 1.8em;
            }
        }
    </style>
</head>
<body>
    <!-- Animated falling leaves -->
    <div class="leaf" style="left: 10%; animation-duration: 10s; animation-delay: 0s;"></div>
    <div class="leaf" style="left: 20%; animation-duration: 12s; animation-delay: 1s;"></div>
    <div class="leaf" style="left: 30%; animation-duration: 8s; animation-delay: 2s;"></div>
    <div class="leaf" style="left: 40%; animation-duration: 14s; animation-delay: 0.5s;"></div>
    <div class="leaf" style="left: 50%; animation-duration: 9s; animation-delay: 3s;"></div>
    <div class="leaf" style="left: 60%; animation-duration: 11s; animation-delay: 1.5s;"></div>
    <div class="leaf" style="left: 70%; animation-duration: 13s; animation-delay: 2.5s;"></div>
    <div class="leaf" style="left: 80%; animation-duration: 7s; animation-delay: 0.2s;"></div>
    <div class="leaf" style="left: 90%; animation-duration: 15s; animation-delay: 3.5s;"></div>
    <div class="leaf" style="left: 15%; animation-duration: 10.5s; animation-delay: 4s;"></div>
    <div class="leaf" style="left: 25%; animation-duration: 9.5s; animation-delay: 1.2s;"></div>
    <div class="leaf" style="left: 35%; animation-duration: 11.5s; animation-delay: 2.2s;"></div>
    <div class="leaf" style="left: 45%; animation-duration: 8.5s; animation-delay: 3.2s;"></div>
    <div class="leaf" style="left: 55%; animation-duration: 12.5s; animation-delay: 0.8s;"></div>
    <div class="leaf" style="left: 65%; animation-duration: 9.8s; animation-delay: 1.8s;"></div>
    <div class="leaf" style="left: 75%; animation-duration: 13.5s; animation-delay: 2.8s;"></div>
    <div class="leaf" style="left: 85%; animation-duration: 7.5s; animation-delay: 3.8s;"></div>
    <div class="leaf" style="left: 95%; animation-duration: 14.5s; animation-delay: 0.3s;"></div>

    <nav>
        <div class="logo">MangaLibrary</div>
        <ul>
            <li><a href="home.php">Home</a></li>
            <li><a href="browse.php">Browse</a></li>
        </ul>
    </nav>
    
    <div class="auth-container">
        <h2>Create Account</h2>
        
        <?php if ($message): ?>
            <div class="error-message"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        
        <form class="auth-form" method="POST">
            <input type="text" name="username" placeholder="Username" required>
            <input type="email" name="email" placeholder="Email" required>
            <input type="password" name="password" placeholder="Password" required minlength="6">
            <button type="submit">Register</button>
        </form>
        
        <div class="auth-links">
            <p>Already have an account? <a href="login_fixed.php">Login here</a></p>
        </div>
    </div>
</body>
</html>
