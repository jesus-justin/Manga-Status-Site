<?php
require_once 'auth.php';
require_once 'db.php';

$auth = new Auth($conn);
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = $auth->login($_POST['username'], $_POST['password']);
    if ($result['success']) {
        header('Location: home.php');
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
    <title>Login - Manga Library</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="home.css">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700&family=Rajdhani:wght@400;600&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Rajdhani', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #0a192f 0%, #112240 50%, #0a192f 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
            overflow: hidden;
        }

        /* Geometric background pattern */
        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: 
                linear-gradient(45deg, transparent 48%, rgba(179, 146, 89, 0.1) 50%, transparent 52%),
                linear-gradient(-45deg, transparent 48%, rgba(179, 146, 89, 0.1) 50%, transparent 52%);
            background-size: 60px 60px;
            opacity: 0.3;
            animation: patternMove 20s linear infinite;
        }

        @keyframes patternMove {
            0% { background-position: 0 0; }
            100% { background-position: 60px 60px; }
        }

        /* Tech particles */
        .particle {
            position: absolute;
            background: rgba(179, 146, 89, 0.6);
            border-radius: 50%;
            animation: floatParticle 6s ease-in-out infinite;
            pointer-events: none;
        }

        @keyframes floatParticle {
            0%, 100% { 
                transform: translateY(0px) rotate(0deg) scale(1);
                opacity: 0.3;
            }
            50% { 
                transform: translateY(-20px) rotate(180deg) scale(1.2);
                opacity: 0.8;
            }
        }

        .auth-container {
            position: relative;
            width: 100%;
            max-width: 450px;
            padding: 60px 40px;
            background: rgba(23, 42, 69, 0.8);
            backdrop-filter: blur(15px);
            border-radius: 16px;
            border: 1px solid rgba(179, 146, 89, 0.3);
            box-shadow: 
                0 25px 45px rgba(0, 0, 0, 0.3),
                0 0 0 1px rgba(179, 146, 89, 0.1),
                inset 0 0 0 1px rgba(255, 255, 255, 0.1);
            overflow: hidden;
            z-index: 2;
        }

        .auth-container::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(45deg, transparent, rgba(179, 146, 89, 0.1), transparent);
            transform: rotate(45deg);
            animation: metallicShine 4s linear infinite;
        }

        @keyframes metallicShine {
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
            color: #b39259;
            font-family: 'Orbitron', sans-serif;
            font-size: 2.4em;
            font-weight: 700;
            letter-spacing: 2px;
            text-transform: uppercase;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.5);
            position: relative;
            z-index: 1;
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
            padding: 18px 24px;
            background: rgba(23, 42, 69, 0.6);
            border: 2px solid rgba(179, 146, 89, 0.4);
            border-radius: 8px;
            color: #e0e6ed;
            font-size: 16px;
            font-family: 'Rajdhani', sans-serif;
            font-weight: 600;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            outline: none;
        }

        .auth-form input::placeholder {
            color: rgba(179, 146, 89, 0.7);
            font-weight: 400;
        }

        .auth-form input:focus {
            background: rgba(35, 55, 85, 0.8);
            border-color: #b39259;
            box-shadow: 
                0 0 20px rgba(179, 146, 89, 0.3),
                inset 0 0 10px rgba(179, 146, 89, 0.1);
            transform: translateY(-2px);
        }

        .auth-form button {
            width: 100%;
            padding: 18px;
            background: linear-gradient(135deg, #b39259 0%, #8a6d3b 100%);
            color: #0a192f;
            border: none;
            border-radius: 8px;
            font-family: 'Orbitron', sans-serif;
            font-size: 16px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 
                0 8px 25px rgba(179, 146, 89, 0.3),
                0 4px 6px rgba(0, 0, 0, 0.1);
            position: relative;
            overflow: hidden;
        }

        .auth-form button::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }

        .auth-form button:hover::before {
            left: 100%;
        }

        .auth-form button:hover {
            transform: translateY(-3px);
            box-shadow: 
                0 12px 35px rgba(179, 146, 89, 0.4),
                0 6px 8px rgba(0, 0, 0, 0.15);
            background: linear-gradient(135deg, #c8a97e 0%, #9c7c4c 100%);
        }

        .auth-form button:active {
            transform: translateY(0);
            box-shadow: 
                0 4px 15px rgba(179, 146, 89, 0.3),
                0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .auth-links {
            text-align: center;
            margin-top: 35px;
            position: relative;
            z-index: 1;
        }

        .auth-links p {
            color: rgba(224, 230, 237, 0.9);
            font-family: 'Rajdhani', sans-serif;
            font-weight: 500;
            margin-bottom: 15px;
        }

        .auth-links a {
            color: #b39259;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            padding: 8px 16px;
            border-radius: 6px;
            border: 1px solid rgba(179, 146, 89, 0.3);
            background: rgba(179, 146, 89, 0.1);
        }

        .auth-links a:hover {
            color: #0a192f;
            background: #b39259;
            border-color: #b39259;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(179, 146, 89, 0.3);
            text-decoration: none;
        }

        .success-message, .error-message {
            text-align: center;
            margin-bottom: 25px;
            padding: 16px;
            border-radius: 8px;
            font-family: 'Rajdhani', sans-serif;
            font-weight: 600;
            position: relative;
            z-index: 1;
            backdrop-filter: blur(10px);
            border: 1px solid;
        }

        .success-message {
            color: #b39259;
            background: rgba(179, 146, 89, 0.15);
            border-color: rgba(179, 146, 89, 0.3);
            box-shadow: 0 4px 15px rgba(179, 146, 89, 0.2);
        }

        .error-message {
            color: #ff6b6b;
            background: rgba(255, 107, 107, 0.15);
            border-color: rgba(255, 107, 107, 0.3);
            box-shadow: 0 4px 15px rgba(255, 107, 107, 0.2);
        }

        nav {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            padding: 20px;
            background: rgba(10, 25, 47, 0.9);
            backdrop-filter: blur(10px);
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid rgba(179, 146, 89, 0.2);
            z-index: 3;
        }

        .logo {
            color: #b39259;
            font-family: 'Orbitron', sans-serif;
            font-size: 1.6em;
            font-weight: 700;
            letter-spacing: 1px;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.5);
        }

        nav ul {
            display: flex;
            list-style: none;
            gap: 30px;
        }

        nav a {
            color: rgba(224, 230, 237, 0.9);
            text-decoration: none;
            font-family: 'Rajdhani', sans-serif;
            font-weight: 600;
            transition: all 0.3s ease;
            padding: 8px 16px;
            border-radius: 6px;
        }

        nav a:hover {
            color: #b39259;
            background: rgba(179, 146, 89, 0.1);
            transform: translateY(-2px);
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
    <nav>
        <div class="logo">
          <a href="home.php" style="color: inherit; text-decoration: none; cursor: pointer;">MangaLibrary</a>
        </div>
        <ul>
            <li><a href="home.php">Home</a></li>
            <li><a href="browse.php">Browse</a></li>
        </ul>
    </nav>
    
    <div class="auth-container">
        <h2>Welcome Back</h2>
        
        <?php if (isset($_GET['registered'])): ?>
            <div class="success-message">Registration successful! Please login.</div>
        <?php endif; ?>
        
        <?php if ($message): ?>
            <div class="error-message"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        
        <form class="auth-form" method="POST">
            <input type="text" name="username" placeholder="Username or Email" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit">Login</button>
        </form>
        
        <div class="auth-links">
            <p>Don't have an account? <a href="register.php">Register here</a></p>
        </div>
    </div>

    <script>
        // Create tech particles effect
        function createParticles() {
            const particlesContainer = document.createElement('div');
            particlesContainer.style.position = 'absolute';
            particlesContainer.style.top = '0';
            particlesContainer.style.left = '0';
            particlesContainer.style.width = '100%';
            particlesContainer.style.height = '100%';
            particlesContainer.style.pointerEvents = 'none';
            particlesContainer.style.zIndex = '1';
            document.body.appendChild(particlesContainer);

            for (let i = 0; i < 20; i++) {
                const particle = document.createElement('div');
                particle.className = 'particle';
                
                // Random size between 2-6px
                const size = Math.random() * 4 + 2;
                particle.style.width = `${size}px`;
                particle.style.height = `${size}px`;
                
                // Random position
                particle.style.left = `${Math.random() * 100}%`;
                particle.style.top = `${Math.random() * 100}%`;
                
                // Random animation delay
                particle.style.animationDelay = `${Math.random() * 6}s`;
                
                particlesContainer.appendChild(particle);
            }
        }

        // Initialize particles when page loads
        document.addEventListener('DOMContentLoaded', createParticles);
    </script>
</body>
</html>
