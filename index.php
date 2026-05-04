<?php
require_once __DIR__ . '/templates/header.php';

// Simple splash / redirect handled by template
?>
<section class="splash">
	<h1>Welcome to <?php echo htmlspecialchars($appName); ?></h1>
	<p>Track your manga reading progress securely and privately.</p>
	<p><a href="home.php" class="btn">Go to Dashboard</a></p>
</section>
<?php
require_once __DIR__ . '/templates/footer.php';
?>
