<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require_once __DIR__ . '/../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/csrf.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $username = trim(filter_input(INPUT_POST, 'username', FILTER_SANITIZE_SPECIAL_CHARS));
    $password = $_POST['password'] ?? '';
    $pdo  = getDB();
    $stmt = $pdo->prepare('SELECT id, username, password FROM users WHERE username = ?');
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    if ($user && password_verify($password, $user['password'])) {
        session_regenerate_id(true);
        $_SESSION['user_id']  = $user['id'];
        $_SESSION['username'] = $user['username'];
        header('Location: /inventory/');
        exit;
    }
    $error = 'Invalid username or password.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Login — Hardware Inventory</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<style>
  body {
    background: #070b24;
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    font-family: 'Segoe UI', sans-serif;
  }
  .login-wrap {
    width: 100%;
    max-width: 420px;
    padding: 20px;
  }
  .login-card {
    background: #111c44;
    border: 1px solid #1f2b60;
    border-radius: 16px;
    padding: 40px 36px;
  }
  .login-logo {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 32px;
  }
  .login-logo .logo-icon {
    width: 44px; height: 44px;
    background: #2563eb;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 22px;
  }
  .login-logo .logo-text {
    font-size: 18px;
    font-weight: 700;
    color: #fff;
  }
  .login-logo .logo-sub {
    font-size: 12px;
    color: #8892b0;
  }
  h4 { color: #fff; font-weight: 700; margin-bottom: 6px; }
  .login-sub { color: #8892b0; font-size: 14px; margin-bottom: 28px; }
  .form-label { color: #8892b0; font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: .5px; }
  .form-control {
    background: #0a0f2e;
    border: 1px solid #1f2b60;
    border-radius: 8px;
    color: #fff;
    padding: 11px 14px;
    font-size: 14px;
  }
  .form-control:focus {
    background: #0a0f2e;
    border-color: #2563eb;
    color: #fff;
    box-shadow: 0 0 0 3px rgba(37,99,235,.15);
  }
  .btn-login {
    background: #2563eb;
    color: #fff;
    border: none;
    border-radius: 8px;
    padding: 12px;
    font-size: 15px;
    font-weight: 600;
    width: 100%;
    cursor: pointer;
    transition: background .2s;
  }
  .btn-login:hover { background: #1d4ed8; }
  .alert-danger { background: rgba(239,68,68,.1); border: 1px solid rgba(239,68,68,.2); color: #ef4444; border-radius: 8px; }
</style>
</head>
<body>
<div class="login-wrap">
  <div class="login-card">
    <div class="login-logo">
      <div class="logo-icon">&#128187;</div>
      <div>
        <div class="logo-text">HardwareStock</div>
        <div class="logo-sub">Inventory System</div>
      </div>
    </div>
    <h4>Welcome back</h4>
    <p class="login-sub">Sign in to your account to continue</p>
    <?php if ($error): ?>
      <div class="alert alert-danger mb-3"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>
    <form method="POST">
      <?= csrf_field() ?>
      <div class="mb-3">
        <label class="form-label">Username</label>
        <input type="text" name="username" class="form-control" placeholder="Enter username" required>
      </div>
      <div class="mb-4">
        <label class="form-label">Password</label>
        <input type="password" name="password" class="form-control" placeholder="Enter password" required>
      </div>
      <button type="submit" class="btn-login">Sign In</button>
    </form>
  </div>
</div>
</body>
</html>