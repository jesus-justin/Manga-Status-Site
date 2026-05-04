<?php
require_once __DIR__ . '/../src/EnvManager.php';
$env = \Src\EnvManager::getInstance();
$appName = $env->get('APP_NAME', 'Manga Status');
?><!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="theme-color" content="#12080a">
  <title><?php echo htmlspecialchars($pageTitle ?? $appName); ?></title>
  <link rel="stylesheet" href="/design-tokens.css">
  <link rel="stylesheet" href="/style.css">
  <link rel="stylesheet" href="/accessibility.css">
  <link rel="stylesheet" href="/home.css">
</head>
<body>
<a class="skip-link" href="#main-content">Skip to main content</a>
<header role="banner">
  <nav role="navigation" aria-label="Main Navigation">
    <div class="nav-inner">
      <a href="/" class="brand"><?php echo htmlspecialchars($appName); ?></a>
    </div>
  </nav>
</header>
<div id="main-content" role="main">
