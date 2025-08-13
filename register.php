<?php
require_once 'auth.php';
require_once 'db.php';

$auth = new Auth($conn);
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = $auth->register($_POST['username'], $_POST['email'], $_POST['password']);
    if ($result['success']) {
        header('Location: login.php?registered=1');
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
        .auth-container {
            max-width: 400px;
            margin: 100px auto;
            padding: 40px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.3);
        }
        
        .auth-container h2 {
            text-align: center;
            margin-bottom: 30px;
            color: #fff;
        }
        
        .auth-form input {
            width: 100%;
            padding: 12px;
            margin-bottom: 15px;
            border: 1px solid #444;
            border-radius: 5px;
            background: rgba(255, 255, 255, 0.1);
            color: #fff;
            font-size: 16px;
        }
        
        .auth-form input::placeholder {
            color: #ccc;
        }
        
        .auth-form button {
            width: 100%;
            padding: 12px;
            background: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            transition: background 0.3s;
        }
        
        .auth-form button:hover {
            background: #0056b3;
        }
        
        .auth-links {
            text-align: center;
            margin-top: 20px;
        }
        
        .auth-links a {
            color: #007bff;
            text-decoration: none;
        }
        
        .auth-links a:hover {
            text-decoration: underline;
        }
        
        .error-message {
            color: #ff6b6b;
            text-align: center;
            margin-bottom: 15px;
            padding: 10px;
            background: rgba(255, 107, 107, 0.1);
            border-radius: 5px;
        }
    </style>
</head>
<body>
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
            <p>Already have an account? <a href="login.php">Login here</a></p>
        </div>
    </div>
</body>
</html>
