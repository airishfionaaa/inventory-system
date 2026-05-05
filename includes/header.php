<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/csrf.php';

$pusherKey     = $_ENV['PUSHER_KEY'];
$pusherCluster = $_ENV['PUSHER_CLUSTER'];

if (empty($_SESSION['user_id']) && basename($_SERVER['PHP_SELF']) !== 'login.php') {
    header('Location: /inventory/auth/login.php');
    exit;
}

function h(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Inventory System</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
<script>
  const PUSHER_KEY     = "<?= h($pusherKey) ?>";
  const PUSHER_CLUSTER = "<?= h($pusherCluster) ?>";
</script>
</head>
<body>
<nav class="navbar navbar-dark bg-dark px-4">
  <a class="navbar-brand fw-bold" href="/inventory/">&#128187; Hardware Inventory</a>
  <div class="d-flex gap-3">
    <a class="nav-link text-white" href="/inventory/modules/assets/">Assets</a>
    <a class="nav-link text-white" href="/inventory/modules/stock_in/">Stock-In</a>
    <a class="nav-link text-white" href="/inventory/modules/stock_out/">Stock-Out</a>
    <a class="nav-link text-light" href="/inventory/auth/logout.php">Logout (<?= h($_SESSION['username'] ?? '') ?>)</a>
  </div>
</nav>
<div class="container mt-4">