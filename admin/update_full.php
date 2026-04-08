<?php
session_start();
if (!isset($_SESSION['logged_in'])) exit;
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $section_id = $_POST['section_id'];
    $title = $_POST['section_title'];
    $desc = $_POST['description'];
    
    $upload_dir = '../uploads/';
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

    $sql = "UPDATE site_content SET section_title = ?, description = ?";
    $params = [$title, $desc];

    for ($i = 1; $i <= 3; $i++) {
        $field = "img$i";
        if (!empty($_FILES[$field]['name'])) {
            $file_name = time() . "_" . $i . "_" . basename($_FILES[$field]['name']);
            if (move_uploaded_file($_FILES[$field]['tmp_name'], $upload_dir . $file_name)) {
                $sql .= ", $field = ?";
                $params[] = $file_name;
            }
        }
    }

    $sql .= " WHERE section_id = ?";
    $params[] = $section_id;

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    header('Location: dashboard.php?status=updated');
}
