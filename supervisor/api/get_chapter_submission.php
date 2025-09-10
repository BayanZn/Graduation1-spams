<?php
session_start(); // Make sure session is started

require_once '../../config/db_connect.php';
header('Content-Type: application/json');

$id = (int)($_GET['id'] ?? 0);
$sup_id = $_SESSION['related_id'] ?? 0;

$stmt = $db->prepare("
  SELECT cs.*, cs.chapter_name, pr.project_name, s.full_name AS student_name
  FROM project_chapter_submissions cs
  JOIN students s ON cs.student_id = s.id
  JOIN projects pr ON s.project_id = pr.id
  JOIN project_supervision psv ON pr.id = psv.project_id
  WHERE cs.id = ? AND psv.supervisor_id = ?
");
$stmt->execute([$id, $sup_id]);

if ($row = $stmt->fetch(PDO::FETCH_OBJ)) {
  // ✅ Clean the file path so it's usable in the browser
  $cleanPath = str_replace(['../', './'], '', $row->file_path);
  $row->file_path = '/project-allocation-system/' . ltrim($cleanPath, '/');

  echo json_encode(['status' => 'success', 'data' => $row]);
} else {
  echo json_encode(['status' => 'error', 'message' => 'Not found or unauthorized']);
}
