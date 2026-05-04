</div>
<footer role="contentinfo">
  <div class="footer-inner">
    <p>&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($appName); ?> — Built for reliability.</p>
  </div>
</footer>
<?php if ($env->get('APP_ENV') !== 'production'): ?>
  <div class="dev-badge">Dev Mode</div>
<?php endif; ?>
</body>
</html>
