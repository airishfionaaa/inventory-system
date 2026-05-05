<?php
require_once __DIR__ . '/../config/db.php';

$pdo  = getDB();
$hash = password_hash('admin123', PASSWORD_BCRYPT);
$stmt = $pdo->prepare('INSERT INTO users (username, password) VALUES (?, ?)');
$stmt->execute(['admin', $hash]);
echo 'Admin user created.';