<?php
session_start();
if (!isset($_SESSION['logged_in'])) { exit; }
require_once 'db.php';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $stmt = $pdo->prepare("UPDATE site_content SET title = ?, content = ? WHERE id = ?");
    $stmt->execute([$_POST['title'], $_POST['content'], $_POST['id']]);
    header('Location: dashboard.php');
    exit;
}
