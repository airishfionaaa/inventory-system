<?php
require_once __DIR__ . '/../../includes/header.php';
$pdo = getDB();
$id  = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if ($id) {
    $stmt = $pdo->prepare('DELETE FROM assets WHERE id = ?');
    $stmt->execute([$id]);
}
header('Location: index.php');
exit;